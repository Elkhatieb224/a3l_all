<?php

namespace App\Jobs;

use App\Models\Ad;
use App\Models\SavedSearch;
use App\Models\SavedSearchMatch;
use App\Notifications\SavedSearchMatchNotification;
use App\Support\SavedSearchFilters;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSavedSearchMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $adId)
    {
    }

    public function handle(): void
    {
        $ad = Ad::with(['category', 'subcategory'])->find($this->adId);
        if (!$ad || $ad->status !== 'active') {
            return;
        }

        SavedSearch::with('user')
            ->chunkById(100, function ($chunk) use ($ad) {
                foreach ($chunk as $savedSearch) {
                    $alreadyMatched = SavedSearchMatch::where('saved_search_id', $savedSearch->id)
                        ->where('ad_id', $ad->id)
                        ->exists();
                    if ($alreadyMatched) {
                        continue;
                    }

                    $q = SavedSearchFilters::buildAdsBaseQuery()->where('ads.id', $ad->id);
                    SavedSearchFilters::applyToAdsQuery($q, $savedSearch->filters ?? []);
                    $isMatch = $q->exists();
                    if (!$isMatch) {
                        continue;
                    }

                    SavedSearchMatch::create([
                        'saved_search_id' => $savedSearch->id,
                        'ad_id' => $ad->id,
                    ]);

                    if ($savedSearch->user) {
                        $savedSearch->user->notify(new SavedSearchMatchNotification($savedSearch, $ad));
                    }
                }
            });
    }
}

