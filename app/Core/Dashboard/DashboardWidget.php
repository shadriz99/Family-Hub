<?php

declare(strict_types=1);

namespace App\Core\Dashboard;

use App\Core\Models\Household;
use Illuminate\Contracts\View\View;

interface DashboardWidget
{
    public function key(): string;

    /**
     * Module identifier ('finance', 'organizer', 'asset', 'core').
     */
    public function module(): string;

    /**
     * Display sort order on dashboard.
     */
    public function order(): int;

    /**
     * Whether this widget is enabled for the given household.
     */
    public function isEnabled(Household $household): bool;

    /**
     * Renders the widget view or HTML string.
     */
    public function render(Household $household): View|string;
}
