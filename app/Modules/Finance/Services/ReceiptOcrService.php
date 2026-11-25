<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Support\Money;
use Carbon\CarbonImmutable;
use Throwable;

class ReceiptOcrService
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
            $preparedPath = $this->prepareImageForOcr($filePathOrText);
            $fileToScan = $preparedPath;

            // Check if tesseract OCR binary exists
            $tesseractBin = trim((string) @shell_exec('which tesseract 2>/dev/null'));
            if ($tesseractBin !== '') {
                $escaped = escapeshellarg($fileToScan);

                // Priority 1: PSM 6 (single uniform block of text) - ideal for columnar receipts
                $rawText = (string) @shell_exec("{$tesseractBin} {$escaped} stdout -l ind+eng --psm 6 2>/dev/null");
                $result = $this->extractFromText($rawText);

                // Priority 2: Fallback to PSM 4 (single column variable sizes) if amount not detected
                if ($result['amount'] === null) {
                    $altText = (string) @shell_exec("{$tesseractBin} {$escaped} stdout -l ind+eng --psm 4 2>/dev/null");
                    $altResult = $this->extractFromText($altText);
                    if ($altResult['amount'] !== null || $altResult['confidence'] > $result['confidence']) {
                        $rawText = $altText;
                        $result = $altResult;
                    }
                }

                // Priority 3: Fallback to default PSM if still not detected
                if ($result['amount'] === null) {
                    $defaultText = (string) @shell_exec("{$tesseractBin} {$escaped} stdout -l ind+eng 2>/dev/null");
                    $defaultResult = $this->extractFromText($defaultText);
                    if ($defaultResult['amount'] !== null || $defaultResult['confidence'] > $result['confidence']) {
                        $rawText = $defaultText;
                        $result = $defaultResult;
                    }
                }

                // Priority 4: Default English only if ind+eng produced nothing
                if (trim($rawText) === '') {
                    $rawText = (string) @shell_exec("{$tesseractBin} {$escaped} stdout --psm 6 2>/dev/null");
                    $result = $this->extractFromText($rawText);
                }
            } else {
                // If binary not present, inspect text in filename or metadata fallback
                $rawText = basename($filePathOrText);
                $result = $this->extractFromText($rawText);
            }

            if ($preparedPath !== $filePathOrText && file_exists($preparedPath)) {
                @unlink($preparedPath);
            }

            return $result;
        } else {
            $rawText = $filePathOrText;
        }

        return $this->extractFromText($rawText);
    }

    /**
     * Pre-process image for OCR: fixes smartphone EXIF orientation and normalizes resolution.
     */
    private function prepareImageForOcr(string $filePath): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $filePath;
        }

        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return $filePath;
        }

        [$width, $height] = $imageInfo;

        // Check EXIF orientation (crucial for mobile camera uploads)
        $orientation = 1;
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($filePath);
            if (is_array($exif) && ! empty($exif['Orientation'])) {
                $orientation = (int) $exif['Orientation'];
            }
        }

        $needsRotation = in_array($orientation, [3, 6, 8], true);
        $needsDownscale = ($width > 1800 || $height > 1800);

        if (! $needsRotation && ! $needsDownscale) {
            return $filePath;
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            return $filePath;
        }

        $src = @imagecreatefromstring($content);
        if (! $src) {
            return $filePath;
        }

        // Correct rotation based on EXIF tag
        if ($orientation === 3) {
            $rotated = imagerotate($src, 180, 0);
            imagedestroy($src);
            $src = $rotated;
        } elseif ($orientation === 6) {
            // Rotated 90 CW in sensor, needs -90 / 270
            $rotated = imagerotate($src, -90, 0);
            imagedestroy($src);
            $src = $rotated;
        } elseif ($orientation === 8) {
            $rotated = imagerotate($src, 90, 0);
            imagedestroy($src);
            $src = $rotated;
        }

        if (! $src) {
            return $filePath;
        }

        // Rescale to max 1800px on the longest edge for fast and sharp OCR
        $curW = imagesx($src);
        $curH = imagesy($src);
        $maxDim = 1800;

        if ($curW > $maxDim || $curH > $maxDim) {
            if ($curW >= $curH) {
                $newW = $maxDim;
                $newH = (int) round(($curH / $curW) * $maxDim);
            } else {
                $newH = $maxDim;
                $newW = (int) round(($curW / $curH) * $maxDim);
            }
            $resized = imagecreatetruecolor($newW, $newH);
            if ($resized) {
                imagecopyresampled($resized, $src, 0, 0, 0, 0, $newW, $newH, $curW, $curH);
                imagedestroy($src);
                $src = $resized;
            }
        }

        $tempPath = sys_get_temp_dir() . '/ocr_' . uniqid('', true) . '.jpg';
        imagejpeg($src, $tempPath, 90);
        imagedestroy($src);

        return $tempPath;
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

        // 1. Merchant / Payee detection
        $merchantMap = [
            'Indomaret' => ['Indomaret', 'Indomarco'],
            'Alfamart' => ['Alfamart', 'Sumber Alfaria', 'Alfa Mart'],
            'Alfamidi' => ['Alfamidi', 'Midi Utama'],
            'Superindo' => ['Superindo', 'Super Indo', 'Lion Super Indo'],
            'Hypermart' => ['Hypermart'],
            'Transmart' => ['Transmart', 'Carrefour'],
            'Pertamina' => ['Pertamina', 'SPBU'],
            'Shell' => ['Shell'],
            'BP' => ['BP AKR', 'BP '],
            'Starbucks' => ['Starbucks'],
            'Kopi Kenangan' => ['Kopi Kenangan'],
            'Janji Jiwa' => ['Janji Jiwa'],
            'McDonalds' => ['McDonalds', "McDonald's", 'McD'],
            'KFC' => ['KFC', 'Kentucky Fried Chicken'],
            'Hokben' => ['Hokben', 'Hoka Hoka Bento'],
            'Tokopedia' => ['Tokopedia'],
            'Shopee' => ['Shopee'],
        ];

        foreach ($merchantMap as $canonicalName => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($text, $pattern) !== false) {
                    $payee = $canonicalName;
                    break 2;
                }
            }
        }

        if ($payee === null && ! empty($lines)) {
            // First non-empty line with letters
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (strlen($trimmed) >= 3 && ! preg_match('/^[0-9\W]+$/', $trimmed)) {
                    $payee = substr($trimmed, 0, 50);
                    break;
                }
            }
        }

        // 2. Date detection (YYYY-MM-DD, DD/MM/YYYY, DD.MM.YYYY, DD-MM-YY, DD Month YYYY)
        if (preg_match('/(\b20\d{2}[-\/.]\d{1,2}[-\/.]\d{1,2}\b)/', $text, $matches)) {
            try {
                $date = CarbonImmutable::parse($matches[1]);
            } catch (Throwable) {
                // ignore
            }
        } elseif (preg_match('/(\b\d{1,2}[-\/.]\d{1,2}[-\/.](?:20)?\d{2}\b)/', $text, $matches)) {
            try {
                $parts = preg_split('/[-\/.]/', $matches[1]);
                if ($parts && count($parts) === 3) {
                    $day = (int) $parts[0];
                    $month = (int) $parts[1];
                    $year = (int) $parts[2];
                    if ($year < 100) {
                        $year += 2000;
                    }
                    $date = CarbonImmutable::createFromDate($year, $month, $day);
                }
            } catch (Throwable) {
                // ignore
            }
        } elseif (preg_match('/(\b\d{1,2})\s+([a-zA-Z]{3,9})\s+((?:20)?\d{2})\b/i', $text, $matches)) {
            $monthMap = [
                'jan' => 1, 'januari' => 1, 'january' => 1,
                'feb' => 2, 'februari' => 2, 'february' => 2,
                'mar' => 3, 'maret' => 3, 'march' => 3,
                'apr' => 4, 'april' => 4,
                'mei' => 5, 'may' => 5,
                'jun' => 6, 'juni' => 6, 'june' => 6,
                'jul' => 7, 'juli' => 7, 'july' => 7,
                'agu' => 8, 'ags' => 8, 'agustus' => 8, 'aug' => 8, 'august' => 8,
                'sep' => 9, 'september' => 9,
                'okt' => 10, 'oktober' => 10, 'oct' => 10, 'october' => 10,
                'nov' => 11, 'november' => 11,
                'des' => 12, 'desember' => 12, 'dec' => 12, 'december' => 12,
            ];
            $monthName = strtolower($matches[2]);
            if (isset($monthMap[$monthName])) {
                $day = (int) $matches[1];
                $month = $monthMap[$monthName];
                $year = (int) $matches[3];
                if ($year < 100) {
                    $year += 2000;
                }
                try {
                    $date = CarbonImmutable::createFromDate($year, $month, $day);
                } catch (Throwable) {
                    // ignore
                }
            }
        }

        // 3. Amount detection
        $cleanLines = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));

        // Tier 1: Explicit final total phrases
        $tier1Regex = '/(?:grand\s*total|total\s*(?:bayar|akhir|pembayaran|tagihan|keseluruhan|netto|net)|jumlah\s*(?:bayar|tagihan|pembayaran|akhir)|(?:amount|total|balance)\s*due|net\s*amount)/i';

        // Tier 2: General total phrases
        $tier2Regex = '/(?:total\s*(?:belanja|pembelian|transaksi|penjualan|harga)|jumlah\s*total)/i';

        // Tier 3: Standalone Total / Jumlah / Tagihan (not subtotal, not total item, not total diskon)
        $tier3Regex = '/(?<!sub\s?)(?<!sub-)\b(?:total|tota[li14]|t0tal|jumlah|tagihan)\b/i';

        $tiers = [$tier1Regex, $tier2Regex, $tier3Regex];

        foreach ($tiers as $tierIndex => $regex) {
            // Scan backwards from bottom so bottom-most final total wins
            for ($i = count($cleanLines) - 1; $i >= 0; $i--) {
                $line = $cleanLines[$i];

                if ($tierIndex === 2) {
                    // Tier 3 excludes
                    if (preg_match('/\b(?:sub\s*total|sub-total|subtotal)\b/i', $line)) {
                        continue;
                    }
                    if (preg_match('/total\s*(?:item|items|qty|pcs|barang|kuantitas|rek|line|diskon|disc|discount|potongan|hemat)/i', $line)) {
                        continue;
                    }
                } else {
                    if (preg_match('/\b(?:sub\s*total|sub-total|subtotal)\b/i', $line)) {
                        continue;
                    }
                    if (preg_match('/total\s*(?:item|items|qty|pcs|barang|kuantitas|rek|line)/i', $line)) {
                        continue;
                    }
                }

                if (preg_match($regex, $line)) {
                    // Check numbers on this line
                    if (preg_match_all('/(?:Rp\.?|IDR\.?)?\s*(\d[0-9Ool.,\s]*(?:,-|.-|--)?)/i', $line, $matches)) {
                        $candidates = [];
                        foreach ($matches[1] as $raw) {
                            $parsed = $this->parseAmountString($raw);
                            if ($parsed !== null && $parsed >= 100) {
                                $candidates[] = $parsed;
                            }
                        }
                        if (! empty($candidates)) {
                            $amount = end($candidates);
                            break 2;
                        }
                    }

                    // Check subsequent lines (up to 3 lines down) if line had only label
                    for ($j = $i + 1; $j <= min($i + 3, count($cleanLines) - 1); $j++) {
                        $subLine = $cleanLines[$j];
                        if (preg_match('/\b(?:no\.?\s*(?:receipt|struk|resi|bukti|faktur|transaksi|inv|order)|invoice|kasir|cashier|date|tanggal|jam)\b/i', $subLine)) {
                            continue;
                        }
                        if (preg_match('/\b(?:terima\s*kasih|thank\s*you|tunai|cash|kembali|kembalian)\b/i', $subLine)) {
                            break;
                        }

                        if (preg_match_all('/(?:Rp\.?|IDR\.?)?\s*(\d[0-9Ool.,\s]*(?:,-|.-|--)?)/i', $subLine, $subMatches)) {
                            $candidates = [];
                            foreach ($subMatches[1] as $raw) {
                                $parsed = $this->parseAmountString($raw);
                                if ($parsed !== null && $parsed >= 100) {
                                    $candidates[] = $parsed;
                                }
                            }
                            if (! empty($candidates)) {
                                $amount = end($candidates);
                                break 3;
                            }
                        }
                    }
                }
            }
        }

        // Fallback: look for currency notation Rp/IDR from bottom up, excluding payment/tender lines
        if ($amount === null) {
            for ($i = count($cleanLines) - 1; $i >= 0; $i--) {
                $line = $cleanLines[$i];
                if (preg_match('/\b(?:tunai|cash|kembali|kembalian|change|diskon|disc|potongan|hemat|subtotal|sub\s*total)\b/i', $line)) {
                    continue;
                }
                if (preg_match('/(?:Rp\.?|IDR)\s*(\d[0-9Ool.,\s]*(?:,-|.-|--)?)/i', $line, $match)) {
                    $parsed = $this->parseAmountString($match[1]);
                    if ($parsed !== null && $parsed >= 100) {
                        $amount = $parsed;
                        break;
                    }
                }
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

    public function parseAmountString(string $raw): ?int
    {
        $cleaned = trim($raw);
        // Remove currency prefixes
        $cleaned = preg_replace('/^(?:Rp\.?|IDR\.?)\s*/i', '', $cleaned);
        // Remove trailing dashes/commas e.g. ,- or .- or --
        $cleaned = preg_replace('/[,.]-+\s*$/', '', $cleaned);
        $cleaned = trim($cleaned, " \t\n\r\0\x0B.,");

        // Normalize OCR letter 'O' / 'o' inside numbers (e.g. 50.OOO or 12.O00)
        if (preg_match('/\d[Oo.]+[Oo\d]/', $cleaned)) {
            $cleaned = str_ireplace('o', '0', $cleaned);
        }

        // Check if ends with rb / jt e.g. 1,5jt or 45rb
        if (preg_match('/\s*(?:rb|jt)$/i', $cleaned)) {
            try {
                return Money::parse($cleaned);
            } catch (Throwable) {
                return null;
            }
        }

        // Strip trailing ,00 or .00 (cents)
        $cleaned = preg_replace('/[,.]00$/', '', $cleaned);

        // If formatted with comma thousands e.g. 150,000 or 1,250,000
        if (preg_match('/^\d{1,3}(?:,\d{3})+$/', $cleaned)) {
            $cleaned = str_replace(',', '', $cleaned);
        } elseif (preg_match('/^\d{1,3}(?:\s\d{3})+$/', $cleaned)) {
            $cleaned = str_replace(' ', '', $cleaned);
        }

        try {
            return Money::parse($cleaned);
        } catch (Throwable) {
            $noDots = str_replace(['.', ',', ' '], '', $cleaned);
            if (ctype_digit($noDots)) {
                return (int) $noDots;
            }

            return null;
        }
    }
}
