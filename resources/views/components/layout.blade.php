@props(['title' => config('tonies.page_title'), 'alpine' => false])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @if ($alpine)
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    @endif
</head>
<body>
<div class="wrap">
    {{ $slot }}

    <footer>
        <span>Prices and availability from us.tonies.com and may have changed.</span>
        @auth
            <span>
                <a href="{{ route('admin') }}">Manage wishlist</a> ·
                <form method="post" action="{{ route('logout') }}">@csrf<button class="linkish">Log out</button></form>
            </span>
        @endauth
    </footer>
</div>
</body>
</html>
