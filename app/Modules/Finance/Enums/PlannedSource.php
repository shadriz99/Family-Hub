<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum PlannedSource: string
{
    case Manual = 'manual';
    case RecurringBill = 'recurring_bill';
    case Loan = 'loan';
    case AssetMaintenance = 'asset_maintenance';
    case AssetMaintenancePlan = 'ast_maintenance_plan';
    case AssetDocument = 'asset_document';

    public function label(): string
    {
        return match ($this) {
            self::Manual => __('finance.planned_source_manual'),
            self::RecurringBill => __('finance.planned_source_recurring_bill'),
            self::Loan => __('finance.planned_source_loan'),
            self::AssetMaintenance, self::AssetMaintenancePlan => __('finance.planned_source_asset_maintenance'),
            self::AssetDocument => __('finance.planned_source_asset_document'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Manual => 'document-text',
            self::RecurringBill => 'arrow-path',
            self::Loan => 'banknotes',
            self::AssetMaintenance, self::AssetMaintenancePlan => 'wrench',
            self::AssetDocument => 'document-duplicate',
        };
    }
}
