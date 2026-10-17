<header class="student-page-heading">
    <div><p class="student-eyebrow">{{ $eyebrow ?? 'STUDENT WORKSPACE' }}</p><h1>{{ $heading }}</h1><p>{{ $description }}</p></div>
    @isset($actionUrl)<a href="{{ $actionUrl }}" class="student-primary-button">{{ $actionLabel }} <span aria-hidden="true">→</span></a>@endisset
</header>
