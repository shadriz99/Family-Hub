<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Models\Household;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

final class PurgeSoftDeletedHouseholdsCommand extends Command
{
    protected $signature = 'households:purge-deleted {--days=30 : Purge households deleted older than this number of days}';

    protected $description = 'Permanently purge soft-deleted households and their associated data after retention period';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $threshold = CarbonImmutable::now()->subDays($days);

        $households = Household::onlyTrashed()
            ->where('deleted_at', '<=', $threshold)
            ->get();

        if ($households->isEmpty()) {
            $this->info("No soft-deleted households older than {$days} days found.");

            return self::SUCCESS;
        }

        $childTables = [
            'fin_transaction_splits',
            'fin_transactions',
            'fin_planned_items',
            'fin_recurring_bills',
            'fin_loans',
            'fin_budgets',
            'fin_allocation_plans',
            'fin_accounts',
            'fin_categories',
            'fin_telegram_links',
            'org_shopping_items',
            'org_shopping_lists',
            'org_tasks',
            'org_task_lists',
            'org_notes',
            'org_calendar_events',
            'ast_fuel_logs',
            'ast_parts',
            'ast_maintenance_logs',
            'ast_issues',
            'ast_documents',
            'ast_services',
            'ast_assets',
            'ast_categories',
            'ast_locations',
            'ast_vendors',
            'reminder_deliveries',
            'reminders',
            'household_invitations',
            'household_finance_period_settings',
            'household_members',
        ];

        $purgedCount = 0;

        foreach ($households as $household) {
            $id = $household->id;

            DB::transaction(function () use ($household, $id, $childTables): void {
                foreach ($childTables as $table) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, 'household_id')) {
                        DB::table($table)->where('household_id', $id)->delete();
                    }
                }

                // Storage clean up
                Storage::disk('local')->deleteDirectory("households/{$id}");
                if (Storage::disk('public')->exists("households/{$id}")) {
                    Storage::disk('public')->deleteDirectory("households/{$id}");
                }

                $household->forceDelete();
            });

            Log::info("Purged soft-deleted household [{$id}] and all associated tenant records.");
            $purgedCount++;
        }

        $this->info("Successfully purged {$purgedCount} soft-deleted household(s).");

        return self::SUCCESS;
    }
}
