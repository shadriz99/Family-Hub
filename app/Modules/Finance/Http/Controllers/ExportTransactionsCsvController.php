<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Support\HouseholdContext;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportTransactionsCsvController
{
    public function __invoke(Request $request, HouseholdContext $context): StreamedResponse
    {
        $household = $context->current();

        $query = Transaction::query()
            ->where('household_id', $household->id)
            ->with(['account', 'toAccount', 'category', 'creator'])
            ->orderByDesc('occurred_on')
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->value());
        }

        if ($request->filled('account_id')) {
            $accId = $request->string('account_id')->value();
            $query->where(function ($q) use ($accId) {
                $q->where('account_id', $accId)->orWhere('to_account_id', $accId);
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->string('category_id')->value());
        }

        if ($request->filled('from')) {
            $query->where('occurred_on', '>=', $request->string('from')->value());
        }

        if ($request->filled('to')) {
            $query->where('occurred_on', '<=', $request->string('to')->value());
        }

        $filename = 'transaksi-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query, $context, $household): void {
            $context->runInContext($household, function () use ($query): void {
                $handle = fopen('php://output', 'w');
                if ($handle === false) {
                    return;
                }

                // UTF-8 BOM for Excel compatibility
                fputs($handle, "\xEF\xBB\xBF");

                // CSV Header
                fputcsv($handle, [
                    'Tanggal',
                    'Tipe',
                    'Akun Sumber',
                    'Akun Tujuan',
                    'Kategori',
                    'Alokasi',
                    'Penerima/Pemberi',
                    'Catatan',
                    'Nominal (Rp)',
                    'Pencatat',
                ]);

                $query->chunk(500, function ($transactions) use ($handle): void {
                    foreach ($transactions as $tx) {
                        fputcsv($handle, [
                            $tx->occurred_on->toDateString(),
                            $tx->type->label(),
                            $tx->account->name,
                            $tx->toAccount !== null ? $tx->toAccount->name : '-',
                            $tx->category !== null ? $tx->category->name : '-',
                            $tx->allocation?->label() ?? '-',
                            $tx->payee ?? '-',
                            $tx->note ?? '-',
                            $tx->amount,
                            $tx->creator !== null ? $tx->creator->name : '-',
                        ]);
                    }
                });

                fclose($handle);
            });
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
