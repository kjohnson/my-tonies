<?php

namespace App\Http\Controllers;

use App\Models\CatalogTonie;
use App\Models\OwnedTonie;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function wishlist(): View
    {
        return view('wishlist', [
            'tonies' => CatalogTonie::wishlist()->orderByDesc('available')->orderBy('title')->get(),
            'ownedCount' => $this->owned()->count(),
        ]);
    }

    public function alreadyHave(): View
    {
        return view('already-have', [
            'tonies' => $this->owned(),
            'wishlistCount' => CatalogTonie::wishlist()->count(),
        ]);
    }

    /**
     * Owned Tonies, with duplicate copies of the same Tonie shown once.
     *
     * @return Collection<int, OwnedTonie>
     */
    private function owned(): Collection
    {
        return OwnedTonie::orderBy('title')->get()
            ->unique(fn (OwnedTonie $t) => $t->sales_id ?? $t->cloud_id)
            ->values();
    }
}
