@if(count($mobileItems))
<nav class="mobile-bottom-navigation" aria-label="{{ $mobileLabel }}" data-mobile-navigation>
    @foreach($mobileItems as $item)
        <a href="{{ $item['url'] }}" @if($item['active'] ?? false) aria-current="{{ isset($item['section']) ? 'location' : 'page' }}" @endif @isset($item['section']) data-navigation-section="{{ $item['section'] }}" @endisset>
            @include('partials.navigation-icon', ['icon' => $item['icon']])
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
@endif
