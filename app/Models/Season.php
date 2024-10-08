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

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Season.
 *
 * @property int         $id
 * @property int         $tv_id
 * @property int         $season_number
 * @property string|null $name
 * @property string|null $overview
 * @property string|null $poster
 * @property string|null $air_date
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Season extends Model
{
    /** @use HasFactory<\Database\Factories\SeasonFactory> */
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    /**
     * Has Many Torrents.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Torrent, $this>
     */
    public function torrents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Torrent::class, 'tmdb', 'tv_id')->whereRelation('category', 'tv_meta', '=', true);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Tv, $this>
     */
    public function tv(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tv::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Episode, $this>
     */
    public function episodes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Episode::class)
            ->oldest('episode_number');
    }
}
