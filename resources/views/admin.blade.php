<x-layout title="Manage wishlist" :alpine="true">
    <header>
        <h1>Manage wishlist</h1>
        <p class="sub">Tap the heart on any Tonie we don't have to add it to the <a href="{{ route('wishlist') }}">public wishlist</a>. Tonies leave the wishlist on their own once they show up in our Toniecloud.</p>
    </header>

    @if (session('status'))
        <p class="flash ok">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="flash err">{{ session('error') }}</p>
    @endif

    <div class="toolbar">
        <span class="muted">
            Last synced {{ $syncedAt ? \Illuminate\Support\Carbon::parse($syncedAt)->diffForHumans() : 'never' }}
        </span>
        <form method="post" action="{{ route('admin.sync') }}" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <button class="btn secondary" :disabled="busy" x-text="busy ? 'Syncing…' : 'Sync now'">Sync now</button>
        </form>
    </div>

    <div x-data="picker(@js($tonies))" x-cloak>
        <div class="controls">
            <div class="row">
                <input type="search" x-model="q" placeholder="Search by name or series" aria-label="Search">
                <select x-model="series" aria-label="Series">
                    <option value="">All series</option>
                    <template x-for="s in seriesList" :key="s"><option :value="s" x-text="s"></option></template>
                </select>
                <select x-model="sort" aria-label="Sort">
                    <option value="title">A–Z</option>
                    <option value="new">Newest</option>
                    <option value="price-asc">Price: low to high</option>
                    <option value="price-desc">Price: high to low</option>
                </select>
            </div>
            <div class="row">
                <span class="label">Show</span>
                <button type="button" class="chip" :aria-pressed="String(onlyPicked)" @click="onlyPicked = !onlyPicked">
                    On wishlist (<span x-text="pickedCount"></span>)
                </button>
            </div>
            <div class="row">
                <span class="label">Type</span>
                <template x-for="c in categoryList" :key="c">
                    <button type="button" class="chip" :aria-pressed="String(cats.includes(c))" @click="toggleIn(cats, c)" x-text="c"></button>
                </template>
            </div>
            <div class="row">
                <span class="label">Age</span>
                <template x-for="a in ageList" :key="a">
                    <button type="button" class="chip" :aria-pressed="String(ages.includes(a))" @click="toggleIn(ages, a)" x-text="a"></button>
                </template>
            </div>
        </div>

        <p class="count" aria-live="polite" x-text="countText"></p>
        <p class="flash err" x-show="error" x-text="error"></p>

        <ul class="grid">
            <template x-for="(t, i) in filtered" :key="t.id">
                <li class="card" :class="{ picked: t.wishlisted }">
                    <button type="button" class="heart" :aria-pressed="String(t.wishlisted)"
                            :aria-label="(t.wishlisted ? 'Remove from' : 'Add to') + ' wishlist: ' + t.title"
                            @click="toggle(t)" x-text="t.wishlisted ? '♥' : '♡'"></button>
                    <div class="img"><img :loading="i < 12 ? 'eager' : 'lazy'" :src="t.image" alt="" decoding="async" x-show="t.image"></div>
                    <div class="body">
                        <h2 x-text="t.title"></h2>
                        <p class="meta" x-text="t.meta"></p>
                        <p class="note" x-show="t.note" x-text="t.note"></p>
                        <span class="badge" x-show="!t.available">Sold out right now</span>
                        <div class="foot">
                            <span class="price" x-text="money(t.price)"></span>
                            <a class="buy" :href="t.url" target="_blank" rel="noopener">View at tonies →</a>
                        </div>
                    </div>
                </li>
            </template>
        </ul>
        <p class="empty" x-show="filtered.length === 0">No Tonies match those filters.</p>
    </div>

    <script>
        function picker(tonies) {
            const unique = list => [...new Set(list)].filter(Boolean);
            const sorters = {
                title: (a, b) => a.title.localeCompare(b.title),
                new: (a, b) => b.published.localeCompare(a.published),
                'price-asc': (a, b) => a.price - b.price,
                'price-desc': (a, b) => b.price - a.price,
            };

            return {
                tonies,
                q: '', series: '', sort: 'title', cats: [], ages: [], onlyPicked: false, error: '',
                seriesList: unique(tonies.map(t => t.series)).sort((a, b) => a.localeCompare(b)),
                categoryList: unique(tonies.map(t => t.category)),
                ageList: unique(tonies.flatMap(t => t.ages)).sort((a, b) => parseInt(a, 10) - parseInt(b, 10)),

                get filtered() {
                    const q = this.q.trim().toLowerCase();
                    return this.tonies.filter(t =>
                        (!q || (t.title + ' ' + (t.series || '')).toLowerCase().includes(q)) &&
                        (!this.series || t.series === this.series) &&
                        (!this.cats.length || this.cats.includes(t.category)) &&
                        (!this.ages.length || t.ages.some(a => this.ages.includes(a))) &&
                        (!this.onlyPicked || t.wishlisted)
                    ).sort(sorters[this.sort]);
                },
                get pickedCount() { return this.tonies.filter(t => t.wishlisted).length; },
                get countText() {
                    const n = this.filtered.length, total = this.tonies.length;
                    return n === total ? `${total} Tonies to choose from` : `${n} of ${total} Tonies`;
                },

                toggleIn(list, value) {
                    const i = list.indexOf(value);
                    i === -1 ? list.push(value) : list.splice(i, 1);
                },
                money: n => n.toLocaleString('en-US', { style: 'currency', currency: 'USD' }),

                async toggle(t) {
                    this.error = '';
                    t.wishlisted = !t.wishlisted;
                    try {
                        const res = await fetch(`{{ url('admin/wishlist') }}/${t.id}`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                        });
                        if (!res.ok) throw new Error(res.status);
                        t.wishlisted = (await res.json()).wishlisted;
                    } catch (e) {
                        t.wishlisted = !t.wishlisted;
                        this.error = `Couldn't update "${t.title}". Try again, or reload if you've been logged out.`;
                    }
                },
            };
        }
    </script>
</x-layout>
