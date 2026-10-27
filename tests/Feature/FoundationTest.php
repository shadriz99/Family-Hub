<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Livewire;

it('keeps unreleased modules disabled', function (): void {
    expect(config('modules'))->toBe([
        'finance' => true,
        'organizer' => false,
        'asset' => false,
    ]);
});

it('can query the selected database', function (): void {
    expect((int) DB::selectOne('SELECT 1 AS result')->result)->toBe(1);
});

it('prevents accidental lazy loading outside production', function (): void {
    expect(Model::preventsLazyLoading())->toBeTrue();
});

it('can render a Livewire component', function (): void {
    Livewire::test(new class () extends Component {
        public function render(): string
        {
            return '<div>Livewire foundation ready</div>';
        }
    })->assertSee('Livewire foundation ready');
});
