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
 */

namespace App\Http\Livewire;

use App\DTO\TorrentSearchFiltersDTO;
use App\Models\Category;
use App\Models\Distributor;
use App\Models\History;
use App\Models\Movie;
use App\Models\Region;
use App\Models\Resolution;
use App\Models\Torrent;
use App\Models\TorrentRequest;
use App\Models\Tv;
use App\Models\Type;
use App\Models\User;
use App\Services\Unit3dAnnounce;
use App\Traits\CastLivewireProperties;
use App\Traits\LivewireSort;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use MarcReichel\IGDBLaravel\Models\Game;

class SimilarTorrent extends Component
{
    use CastLivewireProperties;
    use LivewireSort;

    public Category $category;

    public Movie|Tv|Game $work;

    public ?int $tmdbId;

    public ?int $igdbId;

    public string $reason;

    #[Url(history: true)]
    public string $name = '';

    #[Url(history: true)]
    public string $description = '';

    #[Url(history: true)]
    public string $mediainfo = '';

    #[Url(history: true)]
    public string $uploader = '';

    #[Url(history: true)]
    public string $keywords = '';

    #[Url(history: true)]
    public ?int $minSize = null;

    #[Url(history: true)]
    public int $minSizeMultiplier = 1;

    #[Url(history: true)]
    public ?int $maxSize = null;

    #[Url(history: true)]
    public int $maxSizeMultiplier = 1;

    #[Url(history: true)]
    public ?int $episodeNumber = null;

    #[Url(history: true)]
    public ?int $seasonNumber = null;

    /**
     * @var array<int>
     */
    #[Url(history: true)]
    public array $typeIds = [];

    /**
     * @var array<int>
     */
    #[Url(history: true)]
    public array $resolutionIds = [];

    /**
     * @var array<int>
     */
    #[Url(history: true)]
    public array $regionIds = [];

    /**
     * @var array<int>
     */
    #[Url(history: true)]
    public array $distributorIds = [];

    #[Url(history: true)]
    public string $adult = 'any';

    #[Url(history: true)]
    public ?int $playlistId = null;

    /**
     * @var string[]
     */
    #[Url(history: true)]
    public array $free = [];

    #[Url(history: true)]
    public bool $doubleup = false;

    #[Url(history: true)]
    public bool $featured = false;

    #[Url(history: true)]
    public bool $refundable = false;

    #[Url(history: true)]
    public bool $stream = false;

    #[Url(history: true)]
    public bool $sd = false;

    #[Url(history: true)]
    public bool $highspeed = false;

    #[Url(history: true)]
    public bool $bookmarked = false;

    #[Url(history: true)]
    public bool $wished = false;

    #[Url(history: true)]
    public bool $internal = false;

    #[Url(history: true)]
    public bool $personalRelease = false;

    #[Url(history: true)]
    public bool $trumpable = false;

    #[Url(history: true)]
    public bool $alive = false;

    #[Url(history: true)]
    public bool $dying = false;

    #[Url(history: true)]
    public bool $dead = false;

    #[Url(history: true)]
    public bool $graveyard = false;

    #[Url(history: true)]
    public bool $notDownloaded = false;

    #[Url(history: true)]
    public bool $downloaded = false;

    #[Url(history: true)]
    public bool $seeding = false;

    #[Url(history: true)]
    public bool $leeching = false;

    #[Url(history: true)]
    public bool $incomplete = false;

    #TODO: Update URL attributes once Livewire 3 fixes upstream bug. See: https://github.com/livewire/livewire/discussions/7746

    /**
     * @var array<int, bool>
     */
    public array $checked = [];

    public bool $selectPage = false;

    public bool $hideFilledRequests = true;

    #[Url(history: true)]
    public string $sortField = 'bumped_at';

    #[Url(history: true)]
    public string $sortDirection = 'desc';

    /**
     * @var array<string>
     */
    protected $listeners = [
        'destroy' => 'deleteRecords'
    ];

    final public function boot(): void
    {
        if ($this->work instanceof Movie) {
            $this->work->setAttribute('meta', 'movie');
        } elseif ($this->work instanceof Tv) {
            $this->work->setAttribute('meta', 'tv');
        }
    }

    final public function updating(string $field, mixed &$value): void
    {
        $this->castLivewireProperties($field, $value);
    }

    final public function updatedSelectPage(bool $value): void
    {
        $this->checked = $value ? $this->torrents->flatten()->pluck('id')->toArray() : [];
    }

    final public function updatedChecked(): void
    {
        $this->selectPage = false;
    }

    /**
     * @phpstan-ignore missingType.generics (The return type is too complex for phpstan, something to do with lack of support of Collection array shapes)
     */
    #[Computed]
    final public function torrents(): \Illuminate\Support\Collection
    {
        $user = auth()->user();

        return Torrent::query()
            ->with('user:id,username,group_id', 'category', 'type', 'resolution')
            ->withCount(['thanks', 'comments'])
            ->withExists([
                'bookmarks'          => fn ($query) => $query->where('user_id', '=', $user->id),
                'history as seeding' => fn ($query) => $query->where('user_id', '=', $user->id)
                    ->where('active', '=', 1)
                    ->where('seeder', '=', 1),
                'history as leeching' => fn ($query) => $query->where('user_id', '=', $user->id)
                    ->where('active', '=', 1)
                    ->where('seeder', '=', 0),
                'history as not_completed' => fn ($query) => $query->where('user_id', '=', $user->id)
                    ->where('active', '=', 0)
                    ->where('seeder', '=', 0)
                    ->whereNull('completed_at'),
                'history as not_seeding' => fn ($query) => $query->where('user_id', '=', $user->id)
                    ->where('active', '=', 0)
                    ->where(
                        fn ($query) => $query
                            ->where('seeder', '=', 1)
                            ->orWhereNotNull('completed_at')
                    ),
                'trump',
            ])
            ->when(
                $this->category->movie_meta,
                fn ($query) => $query->whereRelation('category', 'movie_meta', '=', true),
            )
            ->when(
                $this->category->tv_meta,
                fn ($query) => $query->whereRelation('category', 'tv_meta', '=', true),
            )
            ->when(
                $this->category->tv_meta || $this->category->movie_meta,
                fn ($query) => $query->where('tmdb', '=', $this->tmdbId),
                fn ($query) => $query->where('igdb', '=', $this->igdbId),
            )
            ->where((new TorrentSearchFiltersDTO(
                name: $this->name,
                description: $this->description,
                mediainfo: $this->mediainfo,
                keywords: $this->keywords ? array_map('trim', explode(',', $this->keywords)) : [],
                uploader: $this->uploader,
                episodeNumber: $this->episodeNumber,
                seasonNumber: $this->seasonNumber,
                minSize: $this->minSize === null ? null : $this->minSize * $this->minSizeMultiplier,
                maxSize: $this->maxSize === null ? null : $this->maxSize * $this->maxSizeMultiplier,
                playlistId: $this->playlistId,
                typeIds: $this->typeIds,
                resolutionIds: $this->resolutionIds,
                free: $this->free,
                doubleup: $this->doubleup,
                featured: $this->featured,
                refundable: $this->refundable,
                internal: $this->internal,
                personalRelease: $this->personalRelease,
                trumpable: $this->trumpable,
                stream: $this->stream,
                sd: $this->sd,
                highspeed: $this->highspeed,
                userBookmarked: $this->bookmarked,
                userWished: $this->wished,
                alive: $this->alive,
                dying: $this->dying,
                dead: $this->dead,
                graveyard: $this->graveyard,
                userDownloaded: match (true) {
                    $this->downloaded    => true,
                    $this->notDownloaded => false,
                    default              => null,
                },
                userSeeder: match (true) {
                    $this->seeding  => true,
                    $this->leeching => false,
                    default         => null,
                },
                userActive: match (true) {
                    $this->seeding  => true,
                    $this->leeching => true,
                    default         => null,
                },
            ))->toSqlQueryBuilder())
            ->orderBy($this->sortField, $this->sortDirection)
            ->get()
            ->when(
                $this->category->movie_meta,
                fn ($torrents) => $this->groupByTypeAndSort($torrents),
                fn ($torrents) => $torrents
                    ->when($this->category->tv_meta, function ($torrents) {
                        return $torrents
                            ->groupBy(fn ($torrent) => $torrent->season_number === 0 ? ($torrent->episode_number === 0 ? 'Complete Pack' : 'Specials') : 'Seasons')
                            ->map(fn ($packOrSpecialOrSeasons, $key) => match ($key) {
                                'Complete Pack' => $this->groupByTypeAndSort($packOrSpecialOrSeasons),
                                'Specials'      => $packOrSpecialOrSeasons
                                    ->groupBy(fn ($torrent) => 'Special '.$torrent->episode_number)
                                    ->sortKeysDesc(SORT_NATURAL)
                                    ->map(fn ($episode) => $this->groupByTypeAndSort($episode)),
                                'Seasons' => $packOrSpecialOrSeasons
                                    ->groupBy(fn ($torrent) => 'Season '.$torrent->season_number)
                                    ->sortKeysDesc(SORT_NATURAL)
                                    ->map(
                                        fn ($season) => $season
                                            ->groupBy(fn ($torrent) => $torrent->episode_number === 0 ? 'Season Pack' : 'Episodes')
                                            ->map(fn ($packOrEpisodes, $key) => match ($key) {
                                                'Season Pack' => $this->groupByTypeAndSort($packOrEpisodes),
                                                'Episodes'    => $packOrEpisodes
                                                    ->groupBy(fn ($torrent) => 'Episode '.$torrent->episode_number)
                                                    ->sortKeysDesc(SORT_NATURAL)
                                                    ->map(fn ($episode) => $this->groupByTypeAndSort($episode)),
                                                default => abort(500, 'Group found that isn\'t one of: Season Pack, Episodes.'),
                                            })
                                    ),
                                default => abort(500, 'Group found that isn\'t one of: Complete Pack, Specials, Seasons'),
                            });
                    })
            );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Torrent>                                         $torrents
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, \App\Models\Torrent>>
     */
    private function groupByTypeAndSort(\Illuminate\Support\Collection $torrents): \Illuminate\Support\Collection
    {
        return $torrents
            ->sortBy('type.position')
            ->values()
            ->groupBy(fn ($torrent) => $torrent->type->name)
            ->map(
                fn ($torrentsBytype) => $torrentsBytype
                    ->sortBy([
                        ['resolution.position', 'asc'],
                        ['name', 'asc'],
                    ])
                    ->values()
            );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, TorrentRequest>
     */
    #[Computed]
    final public function torrentRequests(): \Illuminate\Database\Eloquent\Collection
    {
        return TorrentRequest::with(['user:id,username,group_id', 'user.group', 'category', 'type', 'resolution'])
            ->withCount(['comments'])
            ->where('tmdb', '=', $this->tmdbId)
            ->where('category_id', '=', $this->category->id)
            ->when(
                $this->hideFilledRequests,
                fn ($query) => $query->where(fn ($query) => $query->whereDoesntHave('torrent')->orWhereDoesntHave('approver'))
            )
            ->latest()
            ->get();
    }

    final public function alertConfirm(): void
    {
        if (!auth()->user()->group->is_modo) {
            $this->dispatch('error', type: 'error', message: 'Permission Denied!');

            return;
        }

        $torrents = Torrent::whereKey($this->checked)->pluck('name')->toArray();
        $names = $torrents;
        $this->dispatch(
            'swal:confirm',
            type: 'warning',
            message: 'Are you sure?',
            body: 'If deleted, you will not be able to recover the following files!'.nl2br("\n")
                        .nl2br(implode("\n", $names)),
        );
    }

    final public function deleteRecords(): void
    {
        if (!auth()->user()->group->is_modo) {
            $this->dispatch('error', type: 'error', message: 'Permission Denied!');

            return;
        }

        $torrents = Torrent::whereKey($this->checked)->get();
        $names = [];
        $users = [];
        $title = match (true) {
            $this->category->movie_meta => ($movie = Movie::find($this->tmdbId))->title.' ('.$movie->release_date.')',
            $this->category->tv_meta    => ($tv = Tv::find($this->tmdbId))->name.' ('.$tv->first_air_date.')',
            $this->category->game_meta  => ($game = Game::find($this->igdbId))->name.' ('.$game->first_release_date.')',
            default                     => $torrents->pluck('name')->join(', '),
        };

        foreach ($torrents as $torrent) {
            $names[] = $torrent->name;

            foreach (History::where('torrent_id', '=', $torrent->id)->get() as $pm) {
                if (!\in_array($pm->user_id, $users)) {
                    $users[] = $pm->user_id;
                }
            }

            // Reset Requests
            $torrent->requests()->update([
                'torrent_id' => null,
            ]);

            //Remove Torrent related info
            cache()->forget(\sprintf('torrent:%s', $torrent->info_hash));

            $torrent->comments()->delete();
            $torrent->peers()->delete();
            $torrent->history()->delete();
            $torrent->hitrun()->delete();
            $torrent->files()->delete();
            $torrent->playlists()->detach();
            $torrent->subtitles()->delete();
            $torrent->resurrections()->delete();
            $torrent->featured()->delete();

            $freeleechTokens = $torrent->freeleechTokens();

            foreach ($freeleechTokens->get() as $freeleechToken) {
                cache()->forget('freeleech_token:'.$freeleechToken->user_id.':'.$torrent->id);
            }

            $freeleechTokens->delete();

            cache()->forget('announce-torrents:by-infohash:'.$torrent->info_hash);

            Unit3dAnnounce::removeTorrent($torrent);

            $torrent->delete();
        }

        foreach ($users as $user) {
            User::sendSystemNotificationTo(
                userId: $user,
                subject: 'Bulk Torrents Deleted - '.$title.'! ',
                message: '[b]Attention: [/b] The following torrents have been removed from our site.
            [list]
                [*]'.implode(' [*]', $names).'
            [/list]
            Our system shows that you were either the uploader, a seeder or a leecher on said torrent. We just wanted to let you know you can safely remove it from your client.
                                    [b]Removal Reason: [/b] '.$this->reason,
            );
        }

        $this->checked = [];
        $this->selectPage = false;

        $this->dispatch(
            'swal:modal',
            type: 'success',
            message: 'Torrents Deleted Successfully!',
            text: 'A personal message has been sent to all users that have downloaded these torrents.',
        );
    }

    #[Computed]
    final public function personalFreeleech(): bool
    {
        return cache()->get('personal_freeleech:'.auth()->id()) ?? false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Type>
     */
    #[Computed(seconds: 3600, cache: true)]
    final public function types(): \Illuminate\Database\Eloquent\Collection
    {
        return Type::query()->orderBy('position')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Resolution>
     */
    #[Computed(seconds: 3600, cache: true)]
    final public function resolutions(): \Illuminate\Database\Eloquent\Collection
    {
        return Resolution::query()->orderBy('position')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Region>
     */
    #[Computed(seconds: 3600, cache: true)]
    final public function regions(): \Illuminate\Database\Eloquent\Collection
    {
        return Region::query()->orderBy('position')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Distributor>
     */
    #[Computed(seconds: 3600, cache: true)]
    final public function distributors(): \Illuminate\Database\Eloquent\Collection
    {
        return Distributor::query()->orderBy('name')->get();
    }

    final public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('livewire.similar-torrent', [
            'user'              => auth()->user(),
            'similarTorrents'   => $this->torrents,
            'personalFreeleech' => $this->personalFreeleech,
            'torrentRequests'   => $this->torrentRequests,
            'media'             => $this->work,
            'types'             => $this->types,
            'resolutions'       => $this->resolutions,
            'regions'           => $this->regions,
            'distributors'      => $this->distributors,
        ]);
    }
}
