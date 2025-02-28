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

use App\Models\Warning;
use App\Notifications\UserManualWarningExpire;
use App\Notifications\UserWarningExpire;
use App\Services\Unit3dAnnounce;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class AutoDeactivateWarning extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:deactivate_warning';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically Deactivates User Warnings If Expired';

    /**
     * Execute the console command.
     *
     * @throws Exception|Throwable If there is an error during the execution of the command.
     */
    final public function handle(): void
    {
        $current = Carbon::now();
        $warnings = Warning::with(['warneduser', 'torrenttitle'])
            ->where('active', '=', 1)
            ->get();

        Warning::query()
            ->where('active', '=', true)
            ->where(
                fn ($query) => $query
                    ->where('expires_on', '<=', $current)
                    ->orWhereHas(
                        'torrenttitle.history',
                        fn ($query) => $query
                            ->whereColumn('history.user_id', '=', 'warnings.user_id')
                            ->where('history.seedtime', '>=', config('hitrun.seedtime'))
                    )
            )
            ->chunkById(100, function ($warnings): void {
                foreach ($warnings as $warning) {
                    // Set Records Active To 0 in warnings table
                    $warning->update(['active' => false]);

                    // Send Notifications
                    if ($warning->torrenttitle) {
                        $warning->warneduser->notify(new UserWarningExpire($warning->warneduser, $warning->torrenttitle));
                    } else {
                        $warning->warneduser->notify(new UserManualWarningExpire($warning->warneduser, $warning));
                    }
                }
            });

        // Calculate User Warning Count and Enable DL Priv If Required.
        Warning::with('warneduser')
            ->select(DB::raw('user_id, SUM(active = 1) as value'))
            ->groupBy('user_id')
            ->having('value', '<', config('hitrun.max_warnings'))
            ->whereRelation('warneduser', 'can_download', '=', false)
            ->chunkById(100, function ($warnings): void {
                foreach ($warnings as $warning) {
                    $warning->warneduser->update(['can_download' => 1]);

                    cache()->forget('user:'.$warning->warneduser->passkey);

                    Unit3dAnnounce::addUser($warning->warneduser);
                }
            }, 'user_id');

        $this->comment('Automated Warning Deativation Command Complete');
    }
}
