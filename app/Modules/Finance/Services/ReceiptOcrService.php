<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Support\Money;
use Carbon\CarbonImmutable;
use Throwable;

final class ReceiptOcrService
{
    /**
     * Parses a local receipt image or plain text and suggests merchant, date, and total amount.
     * Guaranteed local execution without any external network transmission.
     *
     * @return array{
     *     payee: string|null,
     *     occurred_on: CarbonImmutable|null,
     *     amount: int|null,
     *     confidence: float,
     *     raw_text: string|null,
     * }
     */
    public function scan(string $filePathOrText): array
    {
        $rawText = '';

        if (file_exists($filePathOrText)) {
            // Check if tesseract OCR binary exists
            $tesseractBin = trim((string) @shell_exec('which tesseract 2>/dev/null'));
            if ($tesseractBin !== '') {
                $escaped = escapeshellarg($filePathOrText);
                $rawText = (string) @shell_exec("{$tesseractBin} {$escaped} stdout --psm 6 2>/dev/null");
            } else {
                // If binary not present, inspect text in filename or metadata fallback
                $rawText = basename($filePathOrText);
            }
        } else {
            $rawText = $filePathOrText;
        }

        return $this->extractFromText($rawText);
    }

    /**
     * Heuristic parser extracting payee, date, and total amount from receipt text.
     *
     * @return array{
     *     payee: string|null,
     *     occurred_on: CarbonImmutable|null,
     *     amount: int|null,
     *     confidence: float,
     *     raw_text: string|null,
     * }
     */
    public function extractFromText(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
        $payee = null;
        $date = null;
        $amount = null;

        // 1. Merchant / Payee detection (usually top 3 lines)
        $knownMerchants = [
            'Indomaret', 'Alfamart', 'Superindo', 'Hypermart', 'Transmart',
            'Pertamina', 'Shell', 'BP', 'Starbucks', 'Kopi Kenangan',
            'Janji Jiwa', 'McDonalds', 'KFC', 'Hokben', 'Tokopedia', 'Shopee',
        ];

        foreach ($knownMerchants as $known) {
            if (stripos($text, $known) !== false) {
                $payee = $known;
                break;
            }
        }

        if ($payee === null && ! empty($lines)) {
            // First non-empty line
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (strlen($trimmed) >= 3 && ! preg_match('/^[0-9\W]+$/', $trimmed)) {
                    $payee = substr($trimmed, 0, 50);
                    break;
                }
            }
        }

        // 2. Date detection (DD/MM/YYYY, YYYY-MM-DD, DD-MM-YYYY)
        if (preg_match('/(\b20\d{2}[-\/.]\d{1,2}[-\/.]\d{1,2}\b)/', $text, $matches)) {
            try {
                $date = CarbonImmutable::parse($matches[1]);
            } catch (Throwable) {
                // ignore
            }
        } elseif (preg_match('/(\b\d{1,2}[-\/.]\d{1,2}[-\/.]20\d{2}\b)/', $text, $matches)) {
            try {
                $parts = preg_split('/[-\/.]/', $matches[1]);
                if ($parts && count($parts) === 3) {
                    $date = CarbonImmutable::createFromDate((int) $parts[2], (int) $parts[1], (int) $parts[0]);
                }
            } catch (Throwable) {
                // ignore
            }
        }

        // 3. Amount detection (Look for TOTAL, GRAND TOTAL, JUMLAH, RP ...)
        if (preg_match('/(?:total|grand\s*total|jumlah|tagihan)\D*([0-9.,]+)/i', $text, $matches)) {
            $parsed = $this->parseAmountString($matches[1]);
            if ($parsed !== null && $parsed > 0) {
                $amount = $parsed;
            }
        }

        // Fallback: look for Rp \d+
        if ($amount === null && preg_match('/(?:Rp\.?|IDR)\s*([0-9.,]+)/i', $text, $matches)) {
            $parsed = $this->parseAmountString($matches[1]);
            if ($parsed !== null && $parsed > 0) {
                $amount = $parsed;
            }
        }

        $confidence = 0.0;
        if ($payee !== null) {
            $confidence += 0.3;
        }
        if ($date !== null) {
            $confidence += 0.3;
        }
        if ($amount !== null) {
            $confidence += 0.4;
        }

        return [
            'payee' => $payee,
            'occurred_on' => $date,
            'amount' => $amount,
            'confidence' => $confidence,
            'raw_text' => $text,
        ];
    }

    private function parseAmountString(string $raw): ?int
    {
        $cleaned = trim($raw, " \t\n\r\0\x0B.,");
        try {
            return Money::parse($cleaned);
        } catch (Throwable) {
            // Try normalizing Indonesian thousands separator (dots to empty)
            $cleanedNoDots = str_replace('.', '', $cleaned);
            try {
                return Money::parse($cleanedNoDots);
            } catch (Throwable) {
                return null;
            }
        }
    }
}
