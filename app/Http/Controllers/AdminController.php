<?php

namespace App\Http\Controllers;

use App\Models\CatalogTonie;
use App\Services\TonieSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

class AdminController extends Controller
{
    public function index(): View
    {
        // Everything we could add, plus wishlist picks that have since sold out
        $tonies = CatalogTonie::where('owned', false)
            ->where(fn ($q) => $q->where('available', true)->orWhereNotNull('wishlisted_at'))
            ->orderBy('title')
            ->get()
            ->map(fn (CatalogTonie $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'category' => $t->category,
                'series' => $t->series,
                'ages' => $t->ages,
                'meta' => $t->meta(),
                'price' => $t->price,
                'image' => $t->thumbnail(),
                'url' => $t->url(),
                'available' => $t->available,
                'published' => $t->published_at?->toDateString() ?? '',
                'note' => $t->note(),
                'wishlisted' => $t->wishlisted_at !== null,
            ]);

        return view('admin', [
            'tonies' => $tonies,
            'syncedAt' => Cache::get(TonieSync::SYNCED_AT_KEY),
        ]);
    }

    public function toggle(CatalogTonie $tonie): JsonResponse
    {
        $tonie->wishlisted_at = $tonie->wishlisted_at ? null : now();
        $tonie->save();

        return response()->json(['wishlisted' => $tonie->wishlisted_at !== null]);
    }

    public function sync(TonieSync $sync): RedirectResponse
    {
        try {
            $result = $sync->run();
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Sync failed: '.$e->getMessage());
        }

        return back()->with('status', "Synced {$result['owned']} owned Tonies and {$result['catalog']} store Tonies.");
    }
}
