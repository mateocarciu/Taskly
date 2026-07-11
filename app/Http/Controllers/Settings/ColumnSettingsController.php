<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Column;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ColumnSettingsController extends Controller
{
    /**
     * Show the column status mapping settings page.
     */
    public function index(Request $request): Response
    {
        $columns = Column::query()
            ->where('team_id', $request->user()->team_id)
            ->orderBy('order')
            ->get();

        return Inertia::render('settings/Columns', [
            'columns' => $columns,
        ]);
    }
}
