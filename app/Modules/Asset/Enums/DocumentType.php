<?php

declare(strict_types=1);

namespace App\Modules\Asset\Enums;

enum DocumentType: string
{
    case Warranty = 'warranty';
    case VehicleTaxAnnual = 'vehicle_tax_annual';
    case VehicleRegistration5y = 'vehicle_registration_5y';
    case Insurance = 'insurance';
    case Inspection = 'inspection';
    case Certificate = 'certificate';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Warranty => 'Garansi',
            self::VehicleTaxAnnual => 'Pajak STNK Tahunan',
            self::VehicleRegistration5y => 'STNK / Plat 5 Tahunan',
            self::Insurance => 'Asuransi',
            self::Inspection => 'Uji KIR / Pemeriksaan',
            self::Certificate => 'Sertifikat',
            self::Other => 'Lainnya',
        };
    }
}
