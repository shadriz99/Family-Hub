<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Actions\CreateHousehold;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class LayoutAndComponentsTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        static $sequence = 0;
        $sequence++;

        return User::create(array_merge([
            'name' => 'Pengguna '.$sequence,
            'email' => 'pengguna'.$sequence.'@example.test',
            'password' => 'Password123!',
        ], $attributes));
    }

    public function test_it_renders_guest_welcome_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Family Hub');
        $response->assertSee('Pusat Kendali Keluarga');
        $response->assertSee(route('login'));
    }

    public function test_it_renders_authenticated_dashboard_with_app_layout(): void
    {
        $owner = $this->createUser(['name' => 'Shadri']);
        $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Shadri');
        $household->onboarded_at = now()->toImmutable();
        $household->save();

        $response = $this->actingAs($owner)->get('/');

        $response->assertOk();
        $response->assertSee('Selamat datang, Shadri');
        $response->assertSee('Keluarga Shadri');

        // Desktop Sidebar & Brand
        $response->assertSee('Family Hub');
        $response->assertSee('Beranda');
        $response->assertSee('Keuangan');
        $response->assertSee('Kalender');

        // Mobile Bottom Nav & FAB
        $response->assertSee('mobile-fab-btn');
        $response->assertSee('quick-add-sheet');
        $response->assertSee('more-sheet');
    }

    public function test_it_renders_card_component(): void
    {
        $html = Blade::render('<x-card title="Judul Kartu" subtitle="Subjudul" badge="Badge Aktif"><p>Konten</p></x-card>');

        $this->assertStringContainsString('Judul Kartu', $html);
        $this->assertStringContainsString('Subjudul', $html);
        $this->assertStringContainsString('Badge Aktif', $html);
        $this->assertStringContainsString('Konten', $html);
        $this->assertStringContainsString('rounded-3xl', $html);
    }

    public function test_it_renders_datetime_component(): void
    {
        $instant = CarbonImmutable::parse('2026-10-27 15:30:00', 'UTC');
        $html = Blade::render('<x-datetime :value="$instant" timezone="Asia/Jakarta" />', ['instant' => $instant]);

        $this->assertStringContainsString('datetime="2026-10-27T22:30:00+07:00"', $html);
        $this->assertStringContainsString('22:30', $html);
    }

    public function test_it_renders_badge_status_colors(): void
    {
        $overdue = Blade::render('<x-badge-status status="terlambat" />');
        $this->assertStringContainsString('#ad3f45', $overdue);
        $this->assertStringContainsString('Terlambat', $overdue);

        $today = Blade::render('<x-badge-status status="today" />');
        $this->assertStringContainsString('#87571f', $today);
        $this->assertStringContainsString('Hari ini', $today);

        $soon = Blade::render('<x-badge-status status="soon" />');
        $this->assertStringContainsString('#386578', $soon);
        $this->assertStringContainsString('Segera', $soon);

        $done = Blade::render('<x-badge-status status="done" />');
        $this->assertStringContainsString('#28543e', $done);
        $this->assertStringContainsString('Selesai', $done);
    }

    public function test_it_renders_member_avatar_initials_and_roles(): void
    {
        $ownerAvatar = Blade::render('<x-member-avatar name="Shadri Zulkurniadi" role="owner" />');
        $this->assertStringContainsString('SZ', $ownerAvatar);
        $this->assertStringContainsString('#059669', $ownerAvatar);

        $partnerAvatar = Blade::render('<x-member-avatar name="Partner Saya" role="partner" />');
        $this->assertStringContainsString('PS', $partnerAvatar);
        $this->assertStringContainsString('#7C3AED', $partnerAvatar);
    }

    public function test_it_renders_empty_state_component(): void
    {
        $html = Blade::render('<x-empty-state title="Belum ada transaksi" description="Mulai catat transaksi pertama" action-url="/add" action-label="Tambah Transaksi" />');

        $this->assertStringContainsString('Belum ada transaksi', $html);
        $this->assertStringContainsString('Mulai catat transaksi pertama', $html);
        $this->assertStringContainsString('/add', $html);
        $this->assertStringContainsString('Tambah Transaksi', $html);
    }

    public function test_it_renders_progress_bar_with_auto_color_thresholds(): void
    {
        // < 80%: emerald
        $low = Blade::render('<x-progress-bar :value="50" :max="100" show-percent />');
        $this->assertStringContainsString('50%', $low);
        $this->assertStringContainsString('bg-[#28543e]', $low);

        // 80 - 99%: amber
        $mid = Blade::render('<x-progress-bar :value="85" :max="100" />');
        $this->assertStringContainsString('bg-[#87571f]', $mid);

        // >= 100%: rose
        $high = Blade::render('<x-progress-bar :value="110" :max="100" />');
        $this->assertStringContainsString('bg-[#ad3f45]', $high);
    }

    public function test_it_renders_period_switcher_navigation(): void
    {
        $html = Blade::render('<x-period-switcher current="Nov 2026" prev-url="/prev" next-url="/next" />');

        $this->assertStringContainsString('Nov 2026', $html);
        $this->assertStringContainsString('href="/prev"', $html);
        $this->assertStringContainsString('href="/next"', $html);
    }

    public function test_it_renders_filter_chips(): void
    {
        $chips = [
            ['id' => 'all', 'label' => 'Semua', 'count' => 12, 'active' => true],
            ['id' => 'unpaid', 'label' => 'Belum Bayar', 'count' => 3, 'active' => false],
        ];

        $html = Blade::render('<x-filter-chips :items="$chips" />', ['chips' => $chips]);

        $this->assertStringContainsString('Semua', $html);
        $this->assertStringContainsString('12', $html);
        $this->assertStringContainsString('Belum Bayar', $html);
        $this->assertStringContainsString('3', $html);
    }

    public function test_it_renders_confirm_dialog(): void
    {
        $html = Blade::render('<x-confirm id="delete-modal" title="Hapus Akun" message="Yakin hapus akun?" critical><button>Hapus</button></x-confirm>');

        $this->assertStringContainsString('id="delete-modal"', $html);
        $this->assertStringContainsString('Hapus Akun', $html);
        $this->assertStringContainsString('HAPUS', $html);
    }

    public function test_it_renders_bottom_sheet_modal(): void
    {
        $html = Blade::render('<x-bottom-sheet id="test-sheet" title="Panel Tambah"><p>Isi Sheet</p></x-bottom-sheet>');

        $this->assertStringContainsString('id="test-sheet"', $html);
        $this->assertStringContainsString('Panel Tambah', $html);
        $this->assertStringContainsString('Isi Sheet', $html);
    }
}
