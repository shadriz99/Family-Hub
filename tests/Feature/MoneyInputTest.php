<?php

declare(strict_types=1);

use App\Rules\SafeRupiah;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\Livewire;

it('renders an accessible modelable input without a duplicate wire binding', function (): void {
    app()->setLocale('id');
    $html = Blade::render('<x-input-money id="amount" label="Nominal" wire:model.live="amount" required />');

    expect($html)->toContain('x-modelable="value"', 'wire:model.live="amount"', 'for="amount"', 'id="amount-error"', 'required')
        ->and(substr_count($html, 'wire:model.live'))->toBe(1);
});

it('validates the browser integer contract on the server', function (mixed $value, bool $valid): void {
    expect(Validator::make(['amount' => $value], ['amount' => ['required', new SafeRupiah()]])->passes())->toBe($valid);
})->with([
    [0, true], [-50000, true], [SafeRupiah::MAX, true], [-SafeRupiah::MAX, true],
    [SafeRupiah::MAX + 1, false], [-SafeRupiah::MAX - 1, false],
    ['1250000', false], [1.5, false], [null, false], [[], false],
]);

it('validates model updates and clears stale amounts through Livewire', function (): void {
    Livewire::test(new class () extends Component {
        public mixed $amount = null;

        public function save(): void
        {
            $this->validate(['amount' => ['required', new SafeRupiah()]]);
        }

        public function render(): string
        {
            return '<form wire:submit="save"><x-input-money id="amount" label="Nominal" wire:model="amount" /><button>Save</button></form>';
        }
    })->set('amount', 1250000)->call('save')->assertHasNoErrors()
        ->set('amount', SafeRupiah::MAX + 1)->call('save')->assertHasErrors('amount')
        ->set('amount', null)->call('save')->assertHasErrors('amount');
});
