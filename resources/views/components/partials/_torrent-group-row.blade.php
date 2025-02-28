<td class="torrent-search--grouped__overview">
    <div>
        @if (auth()->user()->group->is_editor || auth()->user()->group->is_modo || auth()->id() === $torrent->user_id)
            <a
                href="{{ route('torrents.edit', ['id' => $torrent->id]) }}"
                title="{{ __('common.edit') }}"
                class="torrent-search--grouped__edit"
            >
                <i class="{{ config('other.font-awesome') }} fa-pencil-alt"></i>
            </a>
        @endif

        <h3 class="torrent-search--grouped__name">
            <a href="{{ route('torrents.show', ['id' => $torrent->id]) }}">
                @switch($media->meta)
                    @case('movie')
                        {{-- Removes the year and everything before it --}}
                        @php
                            $releaseYear = $media->release_date instanceof \Illuminate\Support\Carbon ? $media->release_date->year : (int) $media->release_date;
                        @endphp

                        {{ str_contains($torrent->name, ' / ') ? $torrent->name : \preg_replace('/^.*( ' . implode(' | ', range($releaseYear - 1, $releaseYear + 1)) . ' )/i', '', $torrent->name) }}

                        @break
                    @case('tv')
                        {{-- Removes the year and everything before it. Also removes everything before the following patterns: S01, S01E01, S01E01E02, S01E01E02E03, S01E01-E03, 2000- --}}
                        @php
                            if ($media->first_air_date?->year !== null && $media->last_air_date?->year !== null) {
                                $firstAirDateRange = range($media->first_air_date->year - 1, $media->first_air_date->year + 1);
                                $fullRange = range($media->first_air_date->year - 1, $media->last_air_date->year + 1);
                            } else {
                                $firstAirDateRange = [];
                                $fullRange = [];
                            }
                        @endphp

                        {{ str_contains($torrent->name, ' / ') ? $torrent->name : \preg_replace('/^.*( ' . implode(' | ', $firstAirDateRange) . ' | (?=S\d{2,4}(?:-S\d{2,4})?(?:-?E\d{2,4})*? |' . implode('-|', $fullRange) . '-))/i', '', $torrent->name) }}

                        @break
                @endswitch
            </a>
        </h3>
        @include('components.partials._torrent-icons')
    </div>
</td>
<td class="torrent-search--grouped__download">
    @if (config('torrent.download_check_page') == 1)
        <a
            href="{{ route('download_check', ['id' => $torrent->id]) }}"
            title="{{ __('common.download') }}"
        >
            <i class="{{ config('other.font-awesome') }} fa-download"></i>
        </a>
    @else
        <a
            href="{{ route('download', ['id' => $torrent->id]) }}"
            title="{{ __('common.download') }}"
        >
            <i class="{{ config('other.font-awesome') }} fa-download"></i>
        </a>
    @endif
    @if (config('torrent.magnet') == 1)
        <a
            href="magnet:?dn={{ $torrent->name }}&xt=urn:btih:{{ bin2hex($torrent->info_hash) }}&as={{ route('torrent.download.rsskey', ['id' => $torrent->id, 'rsskey' => auth()->user()->rsskey]) }}&tr={{ route('announce', ['passkey' => $user->passkey]) }}&xl={{ $torrent->size }}"
            title="{{ __('common.magnet') }}"
        >
            <i class="{{ config('other.font-awesome') }} fa-magnet"></i>
        </a>
    @endif
</td>
<td class="torrent-search--grouped__bookmark">
    <button
        class="form__standard-icon-button"
        x-data="bookmark({{ $torrent->id }}, {{ Js::from($torrent->bookmarks_exists) }})"
        x-bind="button"
    >
        <i class="{{ config('other.font-awesome') }}" x-bind="icon"></i>
    </button>
</td>
<td class="torrent-search--grouped__size">
    <span title="{{ $torrent->size }} B">
        {{ $torrent->getSize() }}
    </span>
</td>
<td class="torrent-search--grouped__seeders">
    <a class="text-green" href="{{ route('peers', ['id' => $torrent->id]) }}">
        {{ $torrent->seeders }}
    </a>
</td>
<td class="torrent-search--grouped__leechers">
    <a class="text-red" href="{{ route('peers', ['id' => $torrent->id]) }}">
        {{ $torrent->leechers }}
    </a>
</td>
<td class="torrent-search--grouped__completed">
    <a class="text-orange" href="{{ route('history', ['id' => $torrent->id]) }}">
        {{ $torrent->times_completed }}
    </a>
</td>
<td class="torrent-search--grouped__age">
    <time datetime="{{ $torrent->created_at }}" title="{{ $torrent->created_at }}">
        {{ $torrent->created_at->diffForHumans() }}
    </time>
</td>
