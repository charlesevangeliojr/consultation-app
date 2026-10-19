@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="dashboard-heading">
        <div><p class="text-sm font-semibold uppercase tracking-[0.25em] text-rose-400">Overview</p><h2 class="mt-3 text-3xl font-bold sm:text-4xl">Welcome, {{ auth()->user()->name }}</h2><p class="mt-3 text-sm text-slate-400">A clear view of your school's consultation activity.</p></div>
        <form method="GET" action="{{ route('admin.dashboard') }}" class="dashboard-filter">
            <label for="dashboard-month">Reporting month</label>
            <div><input type="month" id="dashboard-month" name="month" value="{{ $month->format('Y-m') }}" required><button type="submit">Apply</button></div>
        </form>
    </div>
    <p class="dashboard-period">Showing {{ $month->format('F Y') }} · Based on scheduled consultation dates · {{ config('app.timezone') }}</p>
    <dl class="dashboard-stats" aria-label="Consultation totals for {{ $month->format('F Y') }}">
        @foreach([['Total bookings', $total, 'all'], ['Awaiting review', $counts['pending'] ?? 0, 'pending'], ['Confirmed', $counts['confirmed'] ?? 0, 'confirmed'], ['Completed', $counts['completed'] ?? 0, 'completed']] as [$label, $value, $tone])
            <div class="dashboard-panel dashboard-stat"><dt><span class="dashboard-stat-dot" data-tone="{{ $tone }}" aria-hidden="true"></span>{{ $label }}</dt><dd>{{ $value }}</dd><p>{{ $month->format('M Y') }}</p></div>
        @endforeach
    </dl>
    <div class="dashboard-charts">
        <section class="dashboard-panel" aria-labelledby="trend-heading">
            <div class="dashboard-chart-heading"><div><p class="dashboard-eyebrow">ACTIVITY OVER TIME</p><h3 id="trend-heading">Monthly consultations</h3><p>Six months ending {{ $month->format('M Y') }}. Includes all booking statuses.</p></div><span class="dashboard-chart-badge">{{ $trend->sum('total') }} total</span></div>
            <div class="dashboard-bars" role="img" aria-label="Monthly consultation totals: {{ $trend->map(fn ($point) => $point['month']->format('M Y').': '.$point['total'])->implode('; ') }}">
                @foreach($trend as $point)
                    <div class="dashboard-bar-row"><span class="dashboard-bar-label">{{ $point['month']->format('M Y') }}</span><div class="dashboard-bar-track"><span class="dashboard-bar-fill" style="width: {{ round($point['total'] / $maximum * 100, 2) }}%"></span></div><strong>{{ $point['total'] }}</strong></div>
                @endforeach
            </div>
            @if($trend->sum('total') === 0)<p class="dashboard-empty">No consultations in this period yet. Bookings will appear here as students request times.</p>@endif
            <details class="dashboard-data"><summary>View monthly data</summary><div class="dashboard-table-scroll"><table><caption class="sr-only">Monthly consultation totals by status</caption><thead><tr><th scope="col">Month</th>@foreach($statuses as $status)<th scope="col">{{ $status['label'] }}</th>@endforeach<th scope="col">Total</th></tr></thead><tbody>@foreach($trend as $point)<tr><th scope="row">{{ $point['month']->format('M Y') }}</th>@foreach($statuses as $key => $status)<td>{{ $point['counts'][$key] ?? 0 }}</td>@endforeach<td>{{ $point['total'] }}</td></tr>@endforeach</tbody></table></div></details>
        </section>
        <section class="dashboard-panel" aria-labelledby="status-heading">
            <div class="dashboard-chart-heading"><div><p class="dashboard-eyebrow">BOOKING BREAKDOWN</p><h3 id="status-heading">Consultation status</h3><p>{{ $month->format('F Y') }}</p></div></div>
            <div class="dashboard-donut" role="img" aria-label="{{ $total }} bookings in {{ $month->format('F Y') }}: {{ collect($statuses)->map(fn ($status, $key) => $status['label'].': '.($counts[$key] ?? 0))->implode('; ') }}">
                <svg viewBox="0 0 160 160" aria-hidden="true"><circle class="dashboard-donut-track" cx="80" cy="80" r="62" fill="none" stroke-width="16"/>
                    @php $offset = 0; $circumference = 2 * pi() * 62; @endphp
                    @foreach($statuses as $key => $status)
                        @php $length = $total ? ($counts[$key] ?? 0) / $total * $circumference : 0; @endphp
                        @if($length > 0)<circle cx="80" cy="80" r="62" fill="none" stroke="{{ $status['color'] }}" stroke-width="16" stroke-dasharray="{{ $length }} {{ $circumference }}" stroke-dashoffset="{{ -$offset }}" transform="rotate(-90 80 80)"/>@endif
                        @php $offset += $length; @endphp
                    @endforeach
                </svg>
                <div class="dashboard-donut-total"><strong>{{ $total }}</strong><span>{{ \Illuminate\Support\Str::plural('booking', $total) }}</span></div>
            </div>
            <dl class="dashboard-status-list">@foreach($statuses as $key => $status)<div><dt><span style="background: {{ $status['color'] }}" aria-hidden="true"></span>{{ $status['label'] }}</dt><dd><strong>{{ $counts[$key] ?? 0 }}</strong><span>{{ $total ? round(($counts[$key] ?? 0) / $total * 100) : 0 }}%</span></dd></div>@endforeach</dl>
            @if($total === 0)<p class="dashboard-empty">No bookings scheduled this month. Choose another reporting month to explore past activity.</p>@endif
        </section>
    </div>
    <footer class="dashboard-account"><span>Signed in as <strong>{{ auth()->user()->email }}</strong></span><span>{{ auth()->user()->isAdmin() ? 'Full access administrator' : 'Custom module access' }}</span></footer>
@endsection
