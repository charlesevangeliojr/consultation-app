@if(count($mobileItems))
<nav class="mobile-bottom-navigation" aria-label="{{ $mobileLabel }}" data-mobile-navigation>
    <div class="mobile-navigation-dock">
    @foreach($mobileItems as $item)
        <a href="{{ $item['url'] }}" @if($item['active'] ?? false) aria-current="{{ isset($item['section']) ? 'location' : 'page' }}" @endif @isset($item['section']) data-navigation-section="{{ $item['section'] }}" @endisset>
            <span class="mobile-navigation-icon" aria-hidden="true">@include('partials.navigation-icon', ['icon' => $item['icon']])</span>
            <span class="mobile-navigation-label">{{ $item['label'] }}</span>
        </a>
    @endforeach
    </div>
</nav>
@endif
