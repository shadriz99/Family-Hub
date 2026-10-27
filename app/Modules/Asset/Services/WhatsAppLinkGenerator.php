<?php

declare(strict_types=1);

namespace App\Modules\Asset\Services;

use App\Core\Models\Household;
use App\Models\User;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\Vendor;

final class WhatsAppLinkGenerator
{
    /**
     * Normalisasi nomor telepon ke standar 62xxx (BR-AST-040).
     */
    public static function normalize(string $number): ?string
    {
        $cleaned = preg_replace('/[^\d]/', '', $number) ?? '';

        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '62')) {
            // Already starts with 62
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62' . $cleaned;
        }

        $length = strlen($cleaned);
        if ($length < 10 || $length > 15) {
            return null;
        }

        return $cleaned;
    }

    /**
     * Generate URL wa.me dengan template pesan (US-AST-08 AC2).
     */
    public static function generate(
        Vendor $vendor,
        User $user,
        Household $household,
        ?Asset $asset = null,
    ): ?string {
        $rawNumber = $vendor->whatsapp ?? $vendor->phone;
        if (! $rawNumber) {
            return null;
        }

        $phone = self::normalize($rawNumber);
        if (! $phone) {
            return null;
        }

        $assetText = $asset ? $asset->name : 'aset kami';
        $brandModel = '';
        if ($asset && ($asset->brand || $asset->model)) {
            $brandModel = trim(($asset->brand ?? '') . ' ' . ($asset->model ?? ''));
            $brandModel = " ({$brandModel})";
        }

        $locationText = $asset?->location?->name ? ' di ' . $asset->location->name : '';

        $message = "Halo {$vendor->name}, saya {$user->name} dari {$household->name}. Ingin servis {$assetText}{$brandModel}{$locationText}. Kapan bisa?";

        return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
    }
}
