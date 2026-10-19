<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = ! empty($data['month']) ? Carbon::createFromFormat('!Y-m', $data['month'])->startOfMonth() : now()->startOfMonth();
        $statuses = [
            'pending' => ['label' => 'Pending', 'color' => '#d97706'],
            'confirmed' => ['label' => 'Confirmed', 'color' => '#0284c7'],
            'completed' => ['label' => 'Completed', 'color' => '#059669'],
            'cancelled' => ['label' => 'Cancelled', 'color' => '#64748b'],
            'rejected' => ['label' => 'Rejected', 'color' => '#e11d48'],
        ];
        // Six bounded aggregate queries; never load names or private reasons.
        $trend = collect(range(5, 0))->map(function ($offset) use ($month) {
            $start = $month->copy()->subMonths($offset);
            $counts = ConsultationAppointment::where('starts_at', '>=', $start)
                ->where('starts_at', '<', $start->copy()->addMonth())
                ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
            return ['month' => $start, 'total' => (int) $counts->sum(), 'counts' => $counts];
        });
        $counts = $trend->last()['counts'];
        $total = $trend->last()['total'];
        $maximum = max(1, $trend->max('total'));

        return view('admin.dashboard', compact('month', 'statuses', 'trend', 'counts', 'total', 'maximum'));
    }
}
