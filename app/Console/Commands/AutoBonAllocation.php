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

namespace App\Console\Commands;

use App\Helpers\ByteUnits;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * @see \Tests\Unit\Console\Commands\AutoBonAllocationTest
 */
class AutoBonAllocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:bon_allocation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allocates Bonus Points To Users Based On Peer Activity.';

    /**
     * Execute the console command.
     */
    public function handle(ByteUnits $byteUnits): void
    {
        $now = now();

        $dyingTorrent = DB::table('peers')
            ->select(DB::raw('count(DISTINCT(peers.torrent_id)) as value'), 'peers.user_id')
            ->join('torrents', 'torrents.id', 'peers.torrent_id')
            ->where('torrents.seeders', 1)
            ->where('torrents.times_completed', '>', 2)
            ->where('peers.seeder', 1)
            ->where('peers.active', 1)
            ->where('peers.created_at', '<', $now->copy()->subMinutes(30))
            ->groupBy('peers.user_id')
            ->get()
            ->toArray();

        $legendaryTorrent = DB::table('peers')
            ->select(DB::raw('count(DISTINCT(peers.torrent_id)) as value'), 'peers.user_id')
            ->join('torrents', 'torrents.id', 'peers.torrent_id')
            ->where('peers.seeder', 1)
            ->where('peers.active', 1)
            ->where('torrents.created_at', '<', $now->copy()->subMonths(12))
            ->where('peers.created_at', '<', $now->copy()->subMinutes(30))
            ->groupBy('peers.user_id')
            ->get()
            ->toArray();

        $oldTorrent = DB::table('peers')
            ->select(DB::raw('count(DISTINCT(peers.torrent_id)) as value'), 'peers.user_id')
            ->join('torrents', 'torrents.id', 'peers.torrent_id')
            ->where('peers.seeder', 1)
            ->where('peers.active', 1)
            ->where('torrents.created_at', '<', $now->copy()->subMonths(6))
            ->where('torrents.created_at', '>', $now->copy()->subMonths(12))
            ->where('peers.created_at', '<', $now->copy()->subMinutes(30))
            ->groupBy('peers.user_id')
            ->get()
            ->toArray();

        $hugeTorrent = DB::table('peers')
            ->select(DB::raw('count(DISTINCT(peers.torrent_id)) as value'), 'peers.user_id')
            ->join('torrents', 'torrents.id', 'peers.torrent_id')
            ->where('peers.seeder', 1)
            ->where('peers.active', 1)
            ->where('torrents.size', '>=', $byteUnits->bytesFromUnit('100GiB'))
            ->where('peers.created_at', '<', $now->copy()->subMinutes(30))
            ->groupBy('peers.user_id')
            ->get()
            ->toArray();

        $largeTorrent = DB::table('peers')
            ->select(DB::raw('count(DISTINCT(peers.torrent_id)) as value'), 'peers.user_id')
            ->join('torrents', 'torrents.id', 'peers.torrent_id')
            ->where('peers.seeder', 1)
            ->where('peers.active', 1)
            ->where('torrents.size', '>=', $byteUnits->bytesFromUnit('25GiB'))
            ->where('torrents.size', '<', $byteUnits->bytesFromUnit('100GiB'))
            ->where('peers.created_at', '<', $now->copy()->subMinutes(30))
            ->groupBy('peers.user_id')
            ->get()
            ->toArray();

        $regularTorrent = DB::table('peers')
            ->select(DB::raw('count(DISTINCT(peers.torrent_id)) as value'), 'peers.user_id')
            ->join('torrents', 'torrents.id', 'peers.torrent_id')
            ->where('peers.seeder', 1)
            ->where('peers.active', 1)
            ->where('torrents.size', '>=', $byteUnits->bytesFromUnit('1GiB'))
            ->where('torrents.size', '<', $byteUnits->bytesFromUnit('25GiB'))
            ->where('peers.created_at', '<', $now->copy()->subMinutes(30))
            ->groupBy('peers.user_id')
            ->get()
            ->toArray();

        $participaintSeeder = DB::table('history')
            ->select(DB::raw('count(*) as value'), 'history.user_id')
            ->where('history.active', 1)
            ->where('history.seedtime', '>=', 2_592_000)
            ->where('history.seedtime', '<', 2_592_000 * 2)
            ->groupBy('history.user_id')
            ->get()
            ->toArray();

        $teamplayerSeeder = DB::table('history')
            ->select(DB::raw('count(*) as value'), 'history.user_id')
            ->where('history.active', 1)
            ->where('history.seedtime', '>=', 2_592_000 * 2)
            ->where('history.seedtime', '<', 2_592_000 * 3)
            ->groupBy('history.user_id')
            ->get()
            ->toArray();

        $commitedSeeder = DB::table('history')
            ->select(DB::raw('count(*) as value'), 'history.user_id')
            ->where('history.active', 1)
            ->where('history.seedtime', '>=', 2_592_000 * 3)
            ->where('history.seedtime', '<', 2_592_000 * 6)
            ->groupBy('history.user_id')
            ->get()
            ->toArray();

        $mvpSeeder = DB::table('history')
            ->select(DB::raw('count(*) as value'), 'history.user_id')
            ->where('history.active', 1)
            ->where('history.seedtime', '>=', 2_592_000 * 6)
            ->where('history.seedtime', '<', 2_592_000 * 12)
            ->groupBy('history.user_id')
            ->get()
            ->toArray();

        $legendarySeeder = DB::table('history')
            ->select(DB::raw('count(*) as value'), 'history.user_id')
            ->where('history.active', 1)
            ->where('history.seedtime', '>=', 2_592_000 * 12)
            ->groupBy('history.user_id')
            ->get()
            ->toArray();

        //Move data from SQL to array

        $array = [];

        foreach ($dyingTorrent as $value) {
            if (\array_key_exists($value->user_id, $array)) {
                $array[$value->user_id] += $value->value * 2;
            } else {
                $array[$value->user_id] = $value->value * 2;
            }
        }

        foreach ($legendaryTorrent as $value) {
            if (\array_key_exists($value->user_id, $array)) {
                $array[$value->user_id] += $value->value * 1.5;
            } else {
                $array[$value->user_id] = $value->value * 1.5;
            }
        }

        foreach ($oldTorrent as $value) {
            if (\array_key_exists($value->user_id, $array)) {
                $array[$value->user_id] += $value->value * 1;
            } else {
                $array[$value->user_id] = $value->value * 1;
            }
        }

        foreach ($hugeTorrent as $value) {
            if (\array_key_exists($value->user_id, $array)) {
                $array[$value->user_id] += $value->value * 0.75;
            } else {
                $array[$value->user_id] = $value->value * 0.75;
            }
        }

        foreach ($largeTorrent as $value) {
            if (\array_key_exists($value->user_id, $array)) {
                $array[$value->user_id] += $value->value * 0.50;
            } else {
                $array[$value->user_id] = $value->value * 0.50;
            }
        }

        foreach ($regularTorrent as $value) {
            if (\array_key_exists($value->user_id, $array)) {
                $array[$value->user_id] += $value->value * 0.25;
            } else {
                $array[$value->user_id] = $value->value * 0.25;
            }
        }

        foreach ($participaintSeeder as $value) {
            if (\array_key_exists($value->user_id, $array)) {
                $array[$value->user_id] += $value->value * 0.25;
            } else {
                $array[$value->user_id] = $value->value * 0.25;
            }
        }

        foreach ($teamplayerSeeder as $value) {
            if (\array_key_exists($value->user_id, $array)) {
                $array[$value->user_id] += $value->value * 0.50;
            } else {
                $array[$value->user_id] = $value->value * 0.50;
            }
        }

        foreach ($commitedSeeder as $value) {
            if (\array_key_exists($value->user_id, $array)) {
                $array[$value->user_id] += $value->value * 0.75;
            } else {
                $array[$value->user_id] = $value->value * 0.75;
            }
        }

        foreach ($mvpSeeder as $value) {
            if (\array_key_exists($value->user_id, $array)) {
                $array[$value->user_id] += $value->value * 1;
            } else {
                $array[$value->user_id] = $value->value * 1;
            }
        }

        foreach ($legendarySeeder as $value) {
            if (\array_key_exists($value->user_id, $array)) {
                $array[$value->user_id] += $value->value * 2;
            } else {
                $array[$value->user_id] = $value->value * 2;
            }
        }

        //Move data from array to BonTransactions table
        /*foreach ($array as $key => $value) {
            $log = new BonTransactions();
            $log->bon_exchange_id = 0;
            $log->name = "Seeding Award";
            $log->cost = $value;
            $log->receiver_id = $key;
            $log->comment = "Seeding Award";
            $log->save();
        }*/

        //Move data from array to Users table
        foreach ($array as $key => $value) {
            User::whereKey($key)->update([
                'seedbonus' => DB::raw('seedbonus + '.$value),
            ]);
        }

        $this->comment('Automated BON Allocation Command Complete');
    }
}
