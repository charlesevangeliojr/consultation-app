@if($siteSettings->logo_path)
    <link rel="preload" as="image" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->logo_path) }}" fetchpriority="high">
@endif
