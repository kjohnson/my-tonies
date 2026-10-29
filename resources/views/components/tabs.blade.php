@props(['wishlistCount', 'ownedCount'])
<nav class="tabs" aria-label="Lists">
    <a href="{{ route('wishlist') }}" @if (request()->routeIs('wishlist')) aria-current="page" @endif>
        Wishlist<span class="count-pill">{{ $wishlistCount }}</span>
    </a>
    <a href="{{ route('already-have') }}" @if (request()->routeIs('already-have')) aria-current="page" @endif>
        Already have<span class="count-pill">{{ $ownedCount }}</span>
    </a>
</nav>
