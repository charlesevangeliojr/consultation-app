@if(($item['badge'] ?? 0) > 0)
    <span class="navigation-count-badge {{ ($mobileBadge ?? false) ? 'navigation-count-badge-mobile' : '' }}" data-navigation-count="{{ $item['badge'] }}" aria-label="{{ $item['badgeLabel'] }}" title="{{ $item['badgeLabel'] }}">{{ $item['badge'] }}</span>
@endif
