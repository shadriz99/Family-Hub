<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Services;

use App\Modules\Organizer\Enums\ShoppingGroup;

final class ShoppingItemParser
{
    /**
     * @return array{name: string, quantity: float, unit: ?string, group: ShoppingGroup}
     */
    public function parse(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            return [
                'name' => '',
                'quantity' => 1.0,
                'unit' => null,
                'group' => ShoppingGroup::Other,
            ];
        }

        $units = ['kg', 'gr', 'gram', 'liter', 'l', 'ml', 'pcs', 'buah', 'biji', 'pak', 'pack', 'dus', 'ikat', 'pouch', 'botol', 'kaleng', 'lembar'];
        $unitRegex = implode('|', $units);

        $quantity = 1.0;
        $unit = null;
        $name = $input;

        // Pattern 1: "telur 2 kg" or "minyak goreng 1.5 liter"
        if (preg_match('/^(.*?)\s+([0-9]+(?:[\.,][0-9]+)?)\s*(' . $unitRegex . ')$/iu', $input, $matches)) {
            $name = trim($matches[1]);
            $quantity = (float) str_replace(',', '.', $matches[2]);
            $unit = strtolower(trim($matches[3]));
        }
        // Pattern 2: "2 kg telur" or "3 pcs sabun"
        elseif (preg_match('/^([0-9]+(?:[\.,][0-9]+)?)\s*(' . $unitRegex . ')\s+(.*)$/iu', $input, $matches)) {
            $quantity = (float) str_replace(',', '.', $matches[1]);
            $unit = strtolower(trim($matches[2]));
            $name = trim($matches[3]);
        }
        // Pattern 3: "telur 2" or "sabun 3"
        elseif (preg_match('/^(.*?)\s+([0-9]+(?:[\.,][0-9]+)?)$/iu', $input, $matches)) {
            $name = trim($matches[1]);
            $quantity = (float) str_replace(',', '.', $matches[2]);
            $unit = 'pcs';
        }

        $group = $this->guessGroup($name);

        return [
            'name' => $name,
            'quantity' => $quantity > 0 ? $quantity : 1.0,
            'unit' => $unit,
            'group' => $group,
        ];
    }

    public function guessGroup(string $name): ShoppingGroup
    {
        $lower = strtolower($name);

        $dictionary = [
            ShoppingGroup::Produce->value => ['sayur', 'bayam', 'kangkung', 'wortel', 'tomat', 'cabai', 'cabe', 'kentang', 'buah', 'apel', 'jeruk', 'pisang', 'semangka', 'melon', 'alpukat', 'timun', 'buncis', 'jamur', 'selada', 'brokoli'],
            ShoppingGroup::MeatSeafood->value => ['daging', 'ayam', 'sapi', 'ikan', 'udang', 'cumi', 'bebek', 'telur', 'sosis', 'nugget', 'bakso', 'salmon', 'tuna'],
            ShoppingGroup::Groceries->value => ['beras', 'minyak', 'gula', 'tepung', 'garam', 'mie', 'mi', 'pasta', 'roti', 'sereal', 'santan'],
            ShoppingGroup::Spices->value => ['bawang', 'merica', 'lada', 'ketumbar', 'jahe', 'kunyit', 'lengkuas', 'kecap', 'saus', 'terasi', 'kaldu', 'kemiri', 'kayu manis'],
            ShoppingGroup::Beverages->value => ['kopi', 'teh', 'susu', 'sirup', 'jus', 'air mineral', 'aqua', 'yakult', 'soda'],
            ShoppingGroup::Cleaning->value => ['sabun', 'shampoo', 'sampo', 'odol', 'pasta gigi', 'deterjen', 'pewangi', 'pembersih', 'spon', 'tisu', 'rinso', 'sunlight', 'molto'],
            ShoppingGroup::Baby->value => ['popok', 'pampers', 'diaper', 'bubur bayi', 'minyak telon', 'bedak bayi'],
        ];

        foreach ($dictionary as $groupKey => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) {
                    return ShoppingGroup::from($groupKey);
                }
            }
        }

        return ShoppingGroup::Other;
    }
}
