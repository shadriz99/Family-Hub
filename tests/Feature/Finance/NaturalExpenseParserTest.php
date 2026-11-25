<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Services\CategorySuggestionService;
use App\Modules\Finance\Services\NaturalExpenseParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::create([
        'name' => 'Shadri Zulkurniadi',
        'email' => 'shadri@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->household = app(CreateHousehold::class)->handle(
        $this->user,
        'Keluarga Uji Coba',
        1,
        'Asia/Jakarta'
    );
    $this->household->onboarded_at = now()->toImmutable();
    $this->household->save();
    app(HouseholdContext::class)->use($this->household);

    Account::create([
        'household_id' => $this->household->id,
        'name' => 'Dompet Kas',
        'type' => AccountType::Cash,
        'opening_balance' => 1_000_000,
        'opening_date' => now()->toDateString(),
        'color' => '#10b981',
        'icon' => 'wallet',
        'created_by' => $this->user->id,
    ]);

    Account::create([
        'household_id' => $this->household->id,
        'name' => 'BCA Syariah',
        'type' => AccountType::Bank,
        'opening_balance' => 5_000_000,
        'opening_date' => now()->toDateString(),
        'color' => '#3b82f6',
        'icon' => 'building-library',
        'created_by' => $this->user->id,
    ]);

    $this->parser = new NaturalExpenseParser(new CategorySuggestionService());
});

it('parses simple amount and payee', function (): void {
    $result = $this->parser->parse($this->household, 'kopi kenangan 25rb');

    expect($result['is_valid'])->toBeTrue()
        ->and($result['amount'])->toBe(25000)
        ->and($result['formatted_amount'])->toBe('Rp 25.000')
        ->and($result['payee'])->toBe('Kopi Kenangan')
        ->and($result['category_id'])->not->toBeNull()
        ->and($result['category_name'])->toContain('Kopi');
});

it('parses amount with k suffix and bca account', function (): void {
    $result = $this->parser->parse($this->household, 'bensin pertamax 50k bca');

    expect($result['is_valid'])->toBeTrue()
        ->and($result['amount'])->toBe(50000)
        ->and($result['payee'])->toBe('Bensin Pertamax')
        ->and($result['account_name'])->toBe('BCA Syariah')
        ->and($result['category_name'])->toContain('Bensin');
});

it('parses reversed order amount first', function (): void {
    $result = $this->parser->parse($this->household, '50.000 makan soto ayam');

    expect($result['is_valid'])->toBeTrue()
        ->and($result['amount'])->toBe(50000)
        ->and($result['payee'])->toBe('Makan Soto Ayam')
        ->and($result['category_id'])->not->toBeNull();
});

it('parses juta nominal with fraction', function (): void {
    $result = $this->parser->parse($this->household, 'servis motor honda 1.5jt');

    expect($result['is_valid'])->toBeTrue()
        ->and($result['amount'])->toBe(1500000)
        ->and($result['payee'])->toBe('Servis Motor Honda');
});

it('removes filler prefixes like beli or bayar', function (): void {
    $result = $this->parser->parse($this->household, 'beli bensin 50rb');

    expect($result['is_valid'])->toBeTrue()
        ->and($result['amount'])->toBe(50000)
        ->and($result['payee'])->toBe('Bensin');
});

it('matches account alias like tunai to Dompet Kas', function (): void {
    $result = $this->parser->parse($this->household, 'kopi kenangan 25k tunai');

    expect($result['is_valid'])->toBeTrue()
        ->and($result['amount'])->toBe(25000)
        ->and($result['payee'])->toBe('Kopi Kenangan')
        ->and($result['account_name'])->toBe('Dompet Kas');
});

it('handles invalid or missing amount', function (): void {
    $result = $this->parser->parse($this->household, 'hallo bot apa kabar');

    expect($result['is_valid'])->toBeFalse()
        ->and($result['error_message'])->toBe('Nominal transaksi tidak terdeteksi.');
});
