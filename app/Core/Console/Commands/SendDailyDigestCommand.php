<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Services\DailyDigestService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class SendDailyDigestCommand extends Command
{
    protected $signature = 'digest:send {--user= : Target specific user ID} {--force : Send regardless of scheduled time window}';

    protected $description = 'Dispatches daily morning digest notifications for households based on local time';

    public function handle(DailyDigestService $digestService): int
    {
        $targetUserId = $this->option('user');
        $force = (bool) $this->option('force');
        $now = CarbonImmutable::now('UTC');

        $this->info("Starting Daily Digest run at {$now->toIso8601String()} UTC...");

        $householdsQuery = Household::query();
        $households = $householdsQuery->get();

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($households as $household) {
            $timezone = $household->timezone ?: 'Asia/Jakarta';
            $localNow = $now->setTimezone($timezone);

            $membersQuery = HouseholdMember::with('user')
                ->where('household_id', $household->id);

            if ($targetUserId !== null) {
                $membersQuery->where('user_id', $targetUserId);
            }

            $members = $membersQuery->get();

            foreach ($members as $member) {
                $user = $member->user;
                if ($user === null || $user->isDisabled()) {
                    continue;
                }

                // Check time window if not forced
                if (! $force && ! $this->matchesTimeWindow($user, $localNow)) {
                    $skippedCount++;
                    continue;
                }

                $sent = $digestService->sendToUser($household, $user, $now, $force);
                if ($sent) {
                    $this->line("Sent daily digest to {$user->email} for household {$household->name}");
                    $sentCount++;
                } else {
                    $skippedCount++;
                }
            }
        }

        $this->info("Daily Digest finished: {$sentCount} sent, {$skippedCount} skipped/suppressed.");

        return Command::SUCCESS;
    }

    /**
     * Checks if local current time is within +/- 15 minutes of the user's daily digest time.
     */
    private function matchesTimeWindow(User $user, CarbonImmutable $localNow): bool
    {
        $digestTimeStr = $user->daily_digest_time ?: '07:00:00';
        $parts = explode(':', $digestTimeStr);
        $hour = (int) ($parts[0] ?? 7);
        $minute = (int) ($parts[1] ?? 0);

        $targetLocal = $localNow->setTime($hour, $minute, 0);
        $diffInMinutes = abs($localNow->diffInMinutes($targetLocal, false));

        return $diffInMinutes <= 15;
    }
}
