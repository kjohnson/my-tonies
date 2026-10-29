<x-layout :alpine="true">
    <header>
        <h1>{{ config('tonies.page_title') }}</h1>
        <p class="sub">The Tonies we already have, so you can avoid doubling up.</p>
    </header>

    <x-tabs :wishlist-count="$wishlistCount" :owned-count="$tonies->count()" />

    @if ($tonies->isEmpty())
        <p class="empty">Nothing here yet.</p>
    @else
        <div x-data="{
            q: new URLSearchParams(location.search).get('q') ?? '',
            items: @js($tonies->map(fn ($t) => mb_strtolower($t->title.' '.$t->series))),
            hit(text) { const q = this.q.trim().toLowerCase(); return !q || text.includes(q); },
            get matches() { return this.items.filter(text => this.hit(text)).length; },
        }">
            <div class="controls">
                <div class="row">
                    <input type="search" x-model="q" placeholder="Do we have it? Search by name or series" aria-label="Search Tonies we have">
                </div>
            </div>

            <p class="count" aria-live="polite" x-show="q.trim()" x-cloak
               x-text="matches ? `Yes, ${matches} match${matches === 1 ? '' : 'es'}` : 'No, we don\'t have that one'"></p>

            <ul class="grid compact">
                @foreach ($tonies as $tonie)
                    <li class="card" x-show="hit(items[{{ $loop->index }}])">
                        <div class="img">
                            @if ($tonie->image)
                                <img src="{{ $tonie->image }}" alt="" loading="{{ $loop->index < 18 ? 'eager' : 'lazy' }}" decoding="async">
                            @endif
                        </div>
                        <div class="body">
                            <h2>{{ $tonie->title }}</h2>
                            @if ($tonie->series && $tonie->series !== $tonie->title)
                                <p class="meta">{{ $tonie->series }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-layout>
