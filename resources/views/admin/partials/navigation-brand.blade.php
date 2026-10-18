<a href="{{ $adminHome }}" class="admin-brand">
    @if($siteSettings->logo_path)<img src="{{ asset('storage/'.$siteSettings->logo_path) }}" alt="">@else<span class="admin-brand-icon">@include('partials.navigation-icon', ['icon' => 'school'])</span>@endif
    <span class="admin-brand-copy"><strong>{{ $siteSettings->name ?: config('app.name') }}</strong><span>Administration</span></span>
</a>
