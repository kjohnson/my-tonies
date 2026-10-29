<x-layout>
    <header>
        <h1>{{ config('tonies.page_title') }}</h1>
        <p class="sub">Tonies we'd love to add to our collection. Each one links to its page on us.tonies.com.</p>
    </header>

    <x-tabs :wishlist-count="$tonies->count()" :owned-count="$ownedCount" />

    @if ($tonies->isEmpty())
        <p class="empty">The wishlist is empty right now.</p>
    @else
        <ul class="grid">
            @foreach ($tonies as $tonie)
                <li class="card">
                    <div class="img">
                        @if ($tonie->thumbnail())
                            <img src="{{ $tonie->thumbnail() }}" alt="" loading="{{ $loop->index < 12 ? 'eager' : 'lazy' }}" decoding="async">
                        @endif
                    </div>
                    <div class="body">
                        <h2>{{ $tonie->title }}</h2>
                        <p class="meta">{{ $tonie->meta() }}</p>
                        @if ($tonie->note())
                            <p class="note">{{ $tonie->note() }}</p>
                        @endif
                        @if ($tonie->preorderLabel())
                            <span class="badge preorder">{{ $tonie->preorderLabel() }}</span>
                        @elseif (! $tonie->available)
                            <span class="badge">Sold out right now</span>
                        @endif
                        <div class="foot">
                            <span class="price">${{ number_format($tonie->price, 2) }}</span>
                            <a class="buy" href="{{ $tonie->url() }}" target="_blank" rel="noopener">View at tonies →</a>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-layout>
