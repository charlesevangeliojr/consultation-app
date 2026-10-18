<nav aria-label="{{ $navigationLabel }}" class="admin-navigation">
    @foreach($adminGroups as $group)
        <div class="admin-navigation-group">
            <p class="admin-navigation-heading">{{ $group['label'] }}</p>
            @foreach($group['items'] as $item)
                <a href="{{ route($item['route']) }}" class="admin-navigation-link" @if(request()->routeIs($item['pattern'])) aria-current="page" @endif>
                    @include('partials.navigation-icon', ['icon' => $item['icon']])<span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    @endforeach
</nav>
