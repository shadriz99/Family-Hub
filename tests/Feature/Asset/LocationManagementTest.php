<?php

declare(strict_types=1);

namespace Tests\Feature\Asset;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Asset\Enums\AssetStatus;
use App\Modules\Asset\Enums\MeterUnit;
use App\Modules\Asset\Livewire\Locations\Index as LocationsIndex;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\AssetCategory;
use App\Modules\Asset\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class LocationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Asset Manager',
            'email' => 'manager@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->household = app(CreateHousehold::class)->handle(
            $this->user,
            'Keluarga Aset',
            1,
            'Asia/Jakarta'
        );
        $this->household->forceFill(['onboarded_at' => now()])->save();
        app(HouseholdContext::class)->use($this->household);

        $this->actingAs($this->user);
    }

    public function test_can_view_locations_index(): void
    {
        Location::create([
            'household_id' => $this->household->id,
            'name' => 'Dapur Bersih',
            'icon' => 'home',
            'sort_order' => 1,
            'created_by' => $this->user->id,
        ]);

        $this->get(route('assets.locations'))
            ->assertOk()
            ->assertSee('Dapur Bersih');
    }

    public function test_can_create_new_location(): void
    {
        Livewire::test(LocationsIndex::class)
            ->set('newName', 'Gudang Belakang')
            ->set('newIcon', 'archive')
            ->call('createLocation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ast_locations', [
            'household_id' => $this->household->id,
            'name' => 'Gudang Belakang',
            'icon' => 'archive',
        ]);
    }

    public function test_create_location_validates_required_name(): void
    {
        Livewire::test(LocationsIndex::class)
            ->set('newName', '')
            ->call('createLocation')
            ->assertHasErrors(['newName' => 'required']);
    }

    public function test_can_edit_location_name(): void
    {
        $location = Location::create([
            'household_id' => $this->household->id,
            'name' => 'Kamar Tamu Lama',
            'icon' => 'bed',
            'sort_order' => 1,
            'created_by' => $this->user->id,
        ]);

        Livewire::test(LocationsIndex::class)
            ->call('edit', $location->id)
            ->assertSet('editingId', $location->id)
            ->assertSet('editName', 'Kamar Tamu Lama')
            ->set('editName', 'Kamar Tamu Baru')
            ->call('updateLocation')
            ->assertHasNoErrors()
            ->assertSet('editingId', null);

        $location->refresh();
        $this->assertSame('Kamar Tamu Baru', $location->name);
    }

    public function test_cannot_delete_location_when_still_used_by_assets(): void
    {
        $location = Location::create([
            'household_id' => $this->household->id,
            'name' => 'Garasi Motor',
            'icon' => 'car',
            'sort_order' => 1,
            'created_by' => $this->user->id,
        ]);

        $category = AssetCategory::create([
            'household_id' => $this->household->id,
            'name' => 'Kendaraan Roda Dua',
            'is_vehicle' => true,
            'meter_unit' => MeterUnit::Km,
            'icon' => 'cycling',
        ]);

        Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => 'Honda Vario 160',
            'status' => AssetStatus::Active,
            'created_by' => $this->user->id,
        ]);

        Livewire::test(LocationsIndex::class)
            ->call('deleteLocation', $location->id)
            ->assertHasErrors(['delete']);

        $this->assertDatabaseHas('ast_locations', [
            'id' => $location->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_delete_location_when_not_used_by_any_assets(): void
    {
        $location = Location::create([
            'household_id' => $this->household->id,
            'name' => 'Ruang Kosong',
            'icon' => 'box',
            'sort_order' => 1,
            'created_by' => $this->user->id,
        ]);

        Livewire::test(LocationsIndex::class)
            ->call('deleteLocation', $location->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('ast_locations', [
            'id' => $location->id,
        ]);
    }

    public function test_cannot_delete_or_edit_location_of_another_household(): void
    {
        $context = app(HouseholdContext::class);

        $otherUser = User::create([
            'name' => 'Other Owner',
            'email' => 'otherowner@example.com',
            'password' => bcrypt('password123'),
        ]);
        $otherHousehold = app(CreateHousehold::class)->handle(
            $otherUser,
            'Keluarga Tetangga',
            1,
            'Asia/Jakarta'
        );

        $otherLocation = null;
        $context->runInContext($otherHousehold, function () use ($otherHousehold, $otherUser, &$otherLocation): void {
            $otherLocation = Location::create([
                'household_id' => $otherHousehold->id,
                'name' => 'Garasi Tetangga',
                'icon' => 'car',
                'sort_order' => 1,
                'created_by' => $otherUser->id,
            ]);
        });

        $context->use($this->household);

        Livewire::test(LocationsIndex::class)
            ->call('deleteLocation', $otherLocation->id)
            ->assertStatus(404);

        $this->assertDatabaseHas('ast_locations', [
            'id' => $otherLocation->id,
        ]);
    }
}
