<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Models\Category;

final class SeedDefaultCategories
{
    /**
     * Seeds the standard Indonesian default categories for the given household.
     */
    public function handle(Household $household, ?User $creator = null): void
    {
        $context = app(HouseholdContext::class);

        $context->runInContext($household, function () use ($household, $creator): void {
            $creatorId = $creator?->getKey();

            // 1. Expense Parent & Sub Categories (Maksimal 5 Kategori Utama per arahan pengguna)
            $expenseDefs = [
                [
                    'name' => 'Kebutuhan Pokok',
                    'icon' => 'home',
                    'color' => '#10b981',
                    'system_key' => 'needs',
                    'default_allocation' => AllocationCategory::Living,
                    'subs' => [
                        ['name' => 'Belanja Dapur', 'default_allocation' => AllocationCategory::Living],
                        ['name' => 'Belanja Bulanan', 'system_key' => 'shopping', 'default_allocation' => AllocationCategory::Living],
                        ['name' => 'Tagihan Rumah', 'default_allocation' => AllocationCategory::Living],
                        ['name' => 'Transportasi & Bensin', 'default_allocation' => AllocationCategory::Living],
                        ['name' => 'Pendidikan', 'default_allocation' => AllocationCategory::Living],
                        ['name' => 'Kesehatan & Obat', 'default_allocation' => AllocationCategory::Living],
                        ['name' => 'Rumah & Perawatan', 'system_key' => 'asset_maintenance', 'default_allocation' => AllocationCategory::Living],
                        [
                            'name' => 'Biaya Admin Bank',
                            'system_key' => 'bank_fee',
                            'default_allocation' => AllocationCategory::Living,
                        ],
                    ],
                ],
                [
                    'name' => 'Lifestyle',
                    'icon' => 'sparkles',
                    'color' => '#8b5cf6',
                    'system_key' => 'lifestyle',
                    'default_allocation' => AllocationCategory::Tertiary,
                    'subs' => [
                        ['name' => 'Makan di Luar', 'default_allocation' => AllocationCategory::Tertiary],
                        ['name' => 'Jajan & Kopi', 'default_allocation' => AllocationCategory::Tertiary],
                        ['name' => 'Hiburan & Streaming', 'default_allocation' => AllocationCategory::Tertiary],
                        ['name' => 'Hobi & Belanja Pribadi', 'default_allocation' => AllocationCategory::Tertiary],
                        ['name' => 'Liburan & Rekreasi', 'default_allocation' => AllocationCategory::Tertiary],
                    ],
                ],
                [
                    'name' => 'Tabungan',
                    'icon' => 'banknotes',
                    'color' => '#06b6d4',
                    'system_key' => 'savings',
                    'default_allocation' => AllocationCategory::Savings,
                    'subs' => [
                        ['name' => 'Dana Darurat', 'default_allocation' => AllocationCategory::Savings],
                        ['name' => 'Tabungan Masa Depan', 'default_allocation' => AllocationCategory::Savings],
                        ['name' => 'Investasi', 'default_allocation' => AllocationCategory::Savings],
                    ],
                ],
                [
                    'name' => 'Donasi',
                    'icon' => 'gift',
                    'color' => '#f59e0b',
                    'system_key' => 'social',
                    'default_allocation' => AllocationCategory::Social,
                    'subs' => [
                        ['name' => 'Zakat & Sedekah', 'default_allocation' => AllocationCategory::Social],
                        ['name' => 'Infaq', 'default_allocation' => AllocationCategory::Social],
                        ['name' => 'Kiriman Orang Tua & Keluarga', 'default_allocation' => AllocationCategory::Social],
                    ],
                ],
                [
                    'name' => 'Hutang',
                    'icon' => 'credit-card',
                    'color' => '#ef4444',
                    'system_key' => 'loan_installment',
                    'default_allocation' => AllocationCategory::Debt,
                    'subs' => [
                        ['name' => 'Cicilan Pinjaman', 'default_allocation' => AllocationCategory::Debt],
                        ['name' => 'KPR', 'default_allocation' => AllocationCategory::Debt],
                        ['name' => 'Cicilan Kendaraan', 'default_allocation' => AllocationCategory::Debt],
                        ['name' => 'Utang Lainnya', 'default_allocation' => AllocationCategory::Debt],
                    ],
                ],
            ];

            $order = 1;

            foreach ($expenseDefs as $def) {
                $parent = Category::create([
                    'household_id' => $household->getKey(),
                    'parent_id' => null,
                    'name' => $def['name'],
                    'type' => CategoryType::Expense,
                    'icon' => $def['icon'],
                    'color' => $def['color'],
                    'system_key' => $def['system_key'],
                    'default_allocation' => $def['default_allocation'],
                    'sort_order' => $order++,
                    'created_by' => $creatorId,
                ]);

                $subOrder = 1;

                foreach ($def['subs'] as $sub) {
                    Category::create([
                        'household_id' => $household->getKey(),
                        'parent_id' => $parent->getKey(),
                        'name' => $sub['name'],
                        'type' => CategoryType::Expense,
                        'icon' => null,
                        'color' => null,
                        'system_key' => $sub['system_key'] ?? null,
                        'default_allocation' => $sub['default_allocation'],
                        'sort_order' => $subOrder++,
                        'created_by' => $creatorId,
                    ]);
                }
            }

            // 2. Income Categories
            $incomeDefs = [
                ['name' => 'Gaji', 'icon' => 'banknotes', 'color' => '#10b981'],
                ['name' => 'Bonus & THR', 'icon' => 'sparkles', 'color' => '#f59e0b'],
                ['name' => 'Usaha/Sampingan', 'icon' => 'briefcase', 'color' => '#3b82f6'],
                ['name' => 'Penerimaan Piutang', 'icon' => 'arrow-down-tray', 'color' => '#06b6d4', 'system_key' => 'loan_receipt'],
                ['name' => 'Pencairan Pinjaman', 'icon' => 'arrow-down-on-square', 'color' => '#8b5cf6', 'system_key' => 'loan_disbursement_in'],
                ['name' => 'Hadiah', 'icon' => 'gift', 'color' => '#ec4899'],
                ['name' => 'Bunga/Bagi Hasil', 'icon' => 'chart-bar', 'color' => '#14b8a6'],
                ['name' => 'Lain-lain', 'icon' => 'ellipsis-horizontal', 'color' => '#94a3b8'],
            ];

            $order = 1;

            foreach ($incomeDefs as $inc) {
                Category::create([
                    'household_id' => $household->getKey(),
                    'parent_id' => null,
                    'name' => $inc['name'],
                    'type' => CategoryType::Income,
                    'icon' => $inc['icon'],
                    'color' => $inc['color'],
                    'system_key' => $inc['system_key'] ?? null,
                    'default_allocation' => null,
                    'sort_order' => $order++,
                    'created_by' => $creatorId,
                ]);
            }

            // 3. Additional System Categories
            // Balance Adjustment (Income & Expense)
            Category::create([
                'household_id' => $household->getKey(),
                'parent_id' => null,
                'name' => 'Penyesuaian Saldo',
                'type' => CategoryType::Income,
                'icon' => 'scale',
                'color' => '#64748b',
                'system_key' => 'balance_adjustment',
                'default_allocation' => null,
                'sort_order' => 99,
                'created_by' => $creatorId,
            ]);

            Category::create([
                'household_id' => $household->getKey(),
                'parent_id' => null,
                'name' => 'Penyesuaian Saldo',
                'type' => CategoryType::Expense,
                'icon' => 'scale',
                'color' => '#64748b',
                'system_key' => 'balance_adjustment',
                'default_allocation' => null,
                'sort_order' => 99,
                'created_by' => $creatorId,
            ]);

            // Loan Disbursement Out (Pemberian Pinjaman)
            Category::create([
                'household_id' => $household->getKey(),
                'parent_id' => null,
                'name' => 'Pemberian Pinjaman',
                'type' => CategoryType::Expense,
                'icon' => 'arrow-up-tray',
                'color' => '#06b6d4',
                'system_key' => 'loan_disbursement_out',
                'default_allocation' => AllocationCategory::Savings,
                'sort_order' => 98,
                'created_by' => $creatorId,
            ]);
        });
    }
}
