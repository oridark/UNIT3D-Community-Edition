<?php

declare(strict_types=1);

/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D Community Edition is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D Community Edition
 *
 * @author     HDVinnie <hdinnovations@protonmail.com>
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 * @credits    Emanuil Rusev <https://erusev.com>
 */

namespace App\Helpers;

use DOMDocument;
use DOMElement;

/**
 * @deprecated Use \GrahamCampbell\Markdown\Facades\Markdown instead
 */
class MarkdownExtra extends Markdown
{
    public function __construct()
    {
        $this->BlockTypes[':'][] = 'DefinitionList';
        $this->BlockTypes['*'][] = 'Abbreviation';

        // identify footnote definitions before reference definitions
        array_unshift($this->BlockTypes['['], 'Footnote');

        // identify footnote markers before before links
        array_unshift($this->InlineTypes['['], 'FootnoteMarker');
    }

    public function text($text)
    {
        $Elements = $this->textElements($text);

        // convert to markup
        $markup = $this->elements($Elements);

        // trim line breaks
        $markup = trim((string) $markup, "\n");

        // merge consecutive dl elements

        $markup = preg_replace('#<\/dl>\s+<dl>\s+#', '', $markup);

        // add footnotes

        if (isset($this->DefinitionData['Footnote'])) {
            $Element = $this->buildFootnoteElement();

            $markup .= "\n".$this->element($Element);
        }

        return $markup;
    }

    //
    // Blocks
    //

    //
    // Abbreviation

    protected function blockAbbreviation($Line)
    {
        if (preg_match('#^\*\[(.+?)\]:[ ]*(.+?)[ ]*$#', (string) $Line['text'], $matches)) {
            $this->DefinitionData['Abbreviation'][$matches[1]] = $matches[2];

            return [
                'hidden' => true,
            ];
        }
    }

    //
    // Footnote

    protected function blockFootnote($Line)
    {
        if (preg_match('#^\[\^(.+?)\]:[ ]?(.*)$#', (string) $Line['text'], $matches)) {
            return [
                'label'  => $matches[1],
                'text'   => $matches[2],
                'hidden' => true,
            ];
        }
    }

    protected function blockFootnoteContinue($Line, $Block)
    {
        if ($Line['text'][0] === '[' && preg_match('#^\[\^(.+?)\]:#', (string) $Line['text'])) {
            return;
        }

        if (isset($Block['interrupted'])) {
            if ($Line['indent'] >= 4) {
                $Block['text'] .= "\n\n".$Line['text'];

                return $Block;
            }
        } else {
            $Block['text'] .= "\n".$Line['text'];

            return $Block;
        }
    }

    protected function blockFootnoteComplete($Block)
    {
        $this->DefinitionData['Footnote'][$Block['label']] = [
            'text'   => $Block['text'],
            'count'  => null,
            'number' => null,
        ];

        return $Block;
    }

    //
    // Definition List

    protected function blockDefinitionList($Line, $Block)
    {
        if (!isset($Block) || $Block['type'] !== 'Paragraph') {
            return;
        }

        $Element = [
            'name'     => 'dl',
            'elements' => [],
        ];

        $terms = explode("\n", (string) $Block['element']['handler']['argument']);

        foreach ($terms as $term) {
            $Element['elements'][] = [
                'name'    => 'dt',
                'handler' => [
                    'function'    => 'lineElements',
                    'argument'    => $term,
                    'destination' => 'elements',
                ],
            ];
        }

        $Block['element'] = $Element;

        return $this->addDdElement($Line, $Block);
    }

    protected function blockDefinitionListContinue($Line, array $Block)
    {
        if ($Line['text'][0] === ':') {
            return $this->addDdElement($Line, $Block);
        }

        if (isset($Block['interrupted']) && $Line['indent'] === 0) {
            return;
        }

        if (isset($Block['interrupted'])) {
            $Block['dd']['handler']['function'] = 'textElements';
            $Block['dd']['handler']['argument'] .= "\n\n";

            $Block['dd']['handler']['destination'] = 'elements';

            unset($Block['interrupted']);
        }

        $text = substr((string) $Line['body'], min($Line['indent'], 4));

        $Block['dd']['handler']['argument'] .= "\n".$text;

        return $Block;
    }

    //
    // Header

    protected function blockHeader($Line)
    {
        $Block = parent::blockHeader($Line);

        if ($Block !== null && preg_match('/[ #]*{('.$this->regexAttribute.'+)}[ ]*$/', (string) $Block['element']['handler']['argument'], $matches, PREG_OFFSET_CAPTURE)) {
            $attributeString = $matches[1][0];

            $Block['element']['attributes'] = $this->parseAttributeData($attributeString);

            $Block['element']['handler']['argument'] = substr((string) $Block['element']['handler']['argument'], 0, $matches[0][1]);
        }

        return $Block;
    }

    //
    // Markup

    protected function blockMarkup($Line)
    {
        if ($this->markupEscaped || $this->safeMode) {
            return;
        }

        if (preg_match('/^<(\w[\w-]*)(?:[ ]*'.$this->regexHtmlAttribute.')*[ ]*(\/)?>/', (string) $Line['text'], $matches)) {
            $element = strtolower($matches[1]);

            if (\in_array($element, $this->textLevelElements)) {
                return;
            }

            $Block = [
                'name'    => $matches[1],
                'depth'   => 0,
                'element' => [
                    'rawHtml'   => $Line['text'],
                    'autobreak' => true,
                ],
            ];

            $length = \strlen((string) $matches[0]);
            $remainder = substr((string) $Line['text'], $length);

            if (trim($remainder) === '') {
                if (isset($matches[2]) || \in_array($matches[1], $this->voidElements)) {
                    $Block['closed'] = true;
                    $Block['void'] = true;
                }
            } else {
                if (isset($matches[2]) || \in_array($matches[1], $this->voidElements)) {
                    return;
                }

                if (preg_match('/<\/'.$matches[1].'>[ ]*$/i', $remainder)) {
                    $Block['closed'] = true;
                }
            }

            return $Block;
        }
    }

    protected function blockMarkupContinue($Line, array $Block)
    {
        if (isset($Block['closed'])) {
            return;
        }

        if (preg_match('/^<'.$Block['name'].'(?:[ ]*'.$this->regexHtmlAttribute.')*[ ]*>/i', (string) $Line['text'])) { // open
            $Block['depth']++;
        }

        if (preg_match('/(.*?)<\/'.$Block['name'].'>[ ]*$/i', (string) $Line['text'], $matches)) { // close
            if ($Block['depth'] > 0) {
                $Block['depth']--;
            } else {
                $Block['closed'] = true;
            }
        }

        if (isset($Block['interrupted'])) {
            $Block['element']['rawHtml'] .= "\n";
            unset($Block['interrupted']);
        }

        $Block['element']['rawHtml'] .= "\n".$Line['body'];

        return $Block;
    }

    protected function blockMarkupComplete($Block)
    {
        if (!isset($Block['void'])) {
            $Block['element']['rawHtml'] = $this->processTag($Block['element']['rawHtml']);
        }

        return $Block;
    }

    //
    // Setext

    protected function blockSetextHeader($Line, array $Block = null)
    {
        $Block = parent::blockSetextHeader($Line, $Block);

        if ($Block !== null && preg_match('/[ ]*{('.$this->regexAttribute.'+)}[ ]*$/', (string) $Block['element']['handler']['argument'], $matches, PREG_OFFSET_CAPTURE)) {
            $attributeString = $matches[1][0];

            $Block['element']['attributes'] = $this->parseAttributeData($attributeString);

            $Block['element']['handler']['argument'] = substr((string) $Block['element']['handler']['argument'], 0, $matches[0][1]);
        }

        return $Block;
    }

    //
    // Inline Elements
    //

    //
    // Footnote Marker

    protected function inlineFootnoteMarker($Excerpt)
    {
        if (preg_match('#^\[\^(.+?)\]#', (string) $Excerpt['text'], $matches)) {
            $name = $matches[1];

            if (!isset($this->DefinitionData['Footnote'][$name])) {
                return;
            }

            $this->DefinitionData['Footnote'][$name]['count']++;

            if (!isset($this->DefinitionData['Footnote'][$name]['number'])) {
                $this->DefinitionData['Footnote'][$name]['number'] = ++$this->footnoteCount; // » &
            }

            $Element = [
                'name'       => 'sup',
                'attributes' => ['id' => 'fnref'.$this->DefinitionData['Footnote'][$name]['count'].':'.$name],
                'element'    => [
                    'name'       => 'a',
                    'attributes' => ['href' => '#fn:'.$name, 'class' => 'footnote-ref'],
                    'text'       => $this->DefinitionData['Footnote'][$name]['number'],
                ],
            ];

            return [
                'extent'  => \strlen((string) $matches[0]),
                'element' => $Element,
            ];
        }
    }

    private int $footnoteCount = 0;

    //
    // Link

    protected function inlineLink($Excerpt)
    {
        $Link = parent::inlineLink($Excerpt);

        $remainder = $Link !== null ? substr((string) $Excerpt['text'], $Link['extent']) : '';

        if (preg_match('/^[ ]*{('.$this->regexAttribute.'+)}/', $remainder, $matches)) {
            $Link['element']['attributes'] += $this->parseAttributeData($matches[1]);

            $Link['extent'] += \strlen((string) $matches[0]);
        }

        return $Link;
    }

    //
    // ~
    //

    private $currentAbreviation;

    private $currentMeaning;

    protected function insertAbreviation(array $Element)
    {
        if (isset($Element['text'])) {
            $Element['elements'] = self::pregReplaceElements(
                '/\b'.preg_quote((string) $this->currentAbreviation, '/').'\b/',
                [
                    [
                        'name'       => 'abbr',
                        'attributes' => [
                            'title' => $this->currentMeaning,
                        ],
                        'text' => $this->currentAbreviation,
                    ],
                ],
                $Element['text']
            );

            unset($Element['text']);
        }

        return $Element;
    }

    protected function inlineText($text)
    {
        $Inline = parent::inlineText($text);

        if (isset($this->DefinitionData['Abbreviation'])) {
            foreach ($this->DefinitionData['Abbreviation'] as $abbreviation => $meaning) {
                $this->currentAbreviation = $abbreviation;
                $this->currentMeaning = $meaning;

                $Inline['element'] = $this->elementApplyRecursiveDepthFirst(
                    fn (array $Element) => $this->insertAbreviation($Element),
                    $Inline['element']
                );
            }
        }

        return $Inline;
    }

    //
    // Util Methods
    //

    protected function addDdElement(array $Line, array $Block)
    {
        $text = substr((string) $Line['text'], 1);
        $text = trim($text);

        unset($Block['dd']);

        $Block['dd'] = [
            'name'    => 'dd',
            'handler' => [
                'function'    => 'lineElements',
                'argument'    => $text,
                'destination' => 'elements',
            ],
        ];

        if (isset($Block['interrupted'])) {
            $Block['dd']['handler']['function'] = 'textElements';

            unset($Block['interrupted']);
        }

        $Block['element']['elements'][] = &$Block['dd'];

        return $Block;
    }

    protected function buildFootnoteElement()
    {
        $Element = [
            'name'       => 'div',
            'attributes' => ['class' => 'footnotes'],
            'elements'   => [
                ['name' => 'hr'],
                [
                    'name'     => 'ol',
                    'elements' => [],
                ],
            ],
        ];

        uasort($this->DefinitionData['Footnote'], 'self::sortFootnotes');

        foreach ($this->DefinitionData['Footnote'] as $definitionId => $DefinitionData) {
            if (!isset($DefinitionData['number'])) {
                continue;
            }

            $text = $DefinitionData['text'];

            $textElements = $this->textElements($text);

            $numbers = range(1, $DefinitionData['count']);

            $backLinkElements = [];

            foreach ($numbers as $number) {
                $backLinkElements[] = ['text' => ' '];
                $backLinkElements[] = [
                    'name'       => 'a',
                    'attributes' => [
                        'href'  => \sprintf('#fnref%s:%s', $number, $definitionId),
                        'rev'   => 'footnote',
                        'class' => 'footnote-backref',
                    ],
                    'rawHtml'                => '&#8617;',
                    'allowRawHtmlInSafeMode' => true,
                    'autobreak'              => false,
                ];
            }

            unset($backLinkElements[0]);

            $n = (is_countable($textElements) ? \count($textElements) : 0) - 1;

            if ($textElements[$n]['name'] === 'p') {
                $backLinkElements = [...[
                    [
                        'rawHtml'                => '&#160;',
                        'allowRawHtmlInSafeMode' => true,
                    ],
                ], ...$backLinkElements];

                unset($textElements[$n]['name']);

                $textElements[$n] = [
                    'name'     => 'p',
                    'elements' => [...[$textElements[$n]], ...$backLinkElements],
                ];
            } else {
                $textElements[] = [
                    'name'     => 'p',
                    'elements' => $backLinkElements,
                ];
            }

            $Element['elements'][1]['elements'][] = [
                'name'       => 'li',
                'attributes' => ['id' => 'fn:'.$definitionId],
                'elements'   => array_merge(
                    $textElements
                ),
            ];
        }

        return $Element;
    }

    // ~

    protected function parseAttributeData($attributeString)
    {
        $Data = [];

        $attributes = preg_split('#[ ]+#', (string) $attributeString, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($attributes as $attribute) {
            if ($attribute[0] === '#') {
                $Data['id'] = substr($attribute, 1);
            } else { // "."
                $classes[] = substr($attribute, 1);
            }
        }

        if (isset($classes)) {
            $Data['class'] = implode(' ', $classes);
        }

        return $Data;
    }

    // ~

    protected function processTag($elementMarkup) // recursive
    {
        // http://stackoverflow.com/q/1148928/200145
        libxml_use_internal_errors(true);

        $DOMDocument = new DOMDocument('1.0', 'utf-8');

        $DOMDocument->loadHTML('<?xml encoding="UTF-8">'.$elementMarkup);

        // http://stackoverflow.com/q/11309194/200145
        foreach ($DOMDocument->childNodes as $item) {
            if ($item->nodeType == XML_PI_NODE) {
                $DOMDocument->removeChild($item);
            }
        }

        $DOMDocument->removeChild($DOMDocument->doctype);
        $DOMDocument->replaceChild($DOMDocument->firstChild->firstChild->firstChild, $DOMDocument->firstChild);

        $elementText = '';

        if ($DOMDocument->documentElement->getAttribute('markdown') === '1') {
            foreach ($DOMDocument->documentElement->childNodes as $Node) {
                $elementText .= $DOMDocument->saveHTML($Node);
            }

            $DOMDocument->documentElement->removeAttribute('markdown');

            $elementText = "\n".$this->text($elementText)."\n";
        } else {
            foreach ($DOMDocument->documentElement->childNodes as $Node) {
                $nodeMarkup = $DOMDocument->saveHTML($Node);

                if ($Node instanceof DOMElement && !\in_array($Node->nodeName, $this->textLevelElements)) {
                    $elementText .= $this->processTag($nodeMarkup);
                } else {
                    $elementText .= $nodeMarkup;
                }
            }
        }

        // because we don't want for markup to get encoded
        $DOMDocument->documentElement->nodeValue = 'placeholder\x1A';

        $markup = $DOMDocument->saveHTML($DOMDocument->documentElement);

        return str_replace('placeholder\x1A', $elementText, $markup);
    }

    // ~

    protected function sortFootnotes($A, $B) // callback
    {
        return $A['number'] - $B['number'];
    }

    //
    // Fields
    //

    protected $regexAttribute = '(?:[#.][-\w]+[ ]*)';
}
