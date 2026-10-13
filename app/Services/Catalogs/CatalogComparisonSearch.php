<?php

namespace App\Services\Catalogs;

use App\Models\ProviderCatalogItem;
use Illuminate\Database\Query\Builder;

class CatalogComparisonSearch
{
    public function candidates(ProviderCatalogItem $selected, string $search): array
    {
        $search = trim($search) ?: mb_substr($selected->name ?: ($selected->code ?? ''), 0, 100);
        $words = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY);
        $query = ProviderCatalogItem::with('snapshot')
            ->join('provider_catalog_snapshots as snapshots', 'snapshots.id', '=', 'provider_catalog_items.snapshot_id')
            ->whereNotExists(function (Builder $newer) {
                $newer->selectRaw('1')->from('provider_catalog_snapshots as newer')
                    ->whereColumn('newer.provider_id', 'snapshots.provider_id')->whereColumn('newer.source_url', 'snapshots.source_url')
                    ->where(function (Builder $dates) {
                        $dates->whereColumn('newer.queried_at', '>', 'snapshots.queried_at')
                            ->orWhere(fn (Builder $sameDate) => $sameDate->whereColumn('newer.queried_at', 'snapshots.queried_at')->whereColumn('newer.id', '>', 'snapshots.id'));
                    });
            });
        if ($words === []) {
            $query->whereRaw('1 = 0');
        }
        foreach ($words as $word) {
            // Treat wildcard characters as literal search text.
            $word = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $word);
            $query->where(fn ($names) => $names->whereRaw("provider_catalog_items.name LIKE ? ESCAPE '!'", ['%'.$word.'%'])
                ->orWhereRaw("provider_catalog_items.code LIKE ? ESCAPE '!'", ['%'.$word.'%']));
        }
        $providerId = $selected->snapshot->provider_id;
        $providers = (clone $query)->where('snapshots.provider_id', '!=', $providerId)
            ->selectRaw('snapshots.provider_id, MAX(snapshots.provider_name) as provider_name')
            ->groupBy('snapshots.provider_id')->orderBy('provider_name')->limit(8)->get();
        $groups = [['provider_id' => (string) $providerId, 'provider' => $selected->snapshot->provider_name,
            'options' => [$selected->comparisonOption()], 'selected' => (string) $selected->id, 'requires_review' => true, 'has_more' => false]];
        foreach ($providers->take(7) as $provider) {
            $rows = (clone $query)->where('snapshots.provider_id', $provider->provider_id)
                ->orderByDesc('snapshots.queried_at')->orderByDesc('snapshots.id')->orderBy('provider_catalog_items.position')
                ->limit(51)->get(['provider_catalog_items.*']);
            $unique = $rows->unique(fn ($item) => json_encode($item->data));
            $hasMore = $rows->count() === 51 || $unique->count() > 20;
            $options = $unique->take(20)->map(fn ($item) => $item->comparisonOption())->values()->all();
            $groups[] = ['provider_id' => (string) $provider->provider_id, 'provider' => $provider->provider_name,
                'options' => $options, 'selected' => count($options) === 1 && ! $hasMore ? $options[0]['value'] : null,
                'requires_review' => true, 'has_more' => $hasMore];
        }

        return ['groups' => $groups, 'search' => $search, 'providers_truncated' => $providers->count() > 7];
    }
}
