<?php

namespace App\Services;

use App\Models\ConcertDetails;
use App\Models\Setlist;
use Illuminate\Support\Facades\DB;

class SetlistService
{
    public function assignToConcert(
        Setlist $source,
        ConcertDetails $concertDetails
    ): Setlist {
        return DB::transaction(function () use ($source, $concertDetails) {

            $source->load('sets.items');

            $concertSetlist = Setlist::create([
                'project_id' => $source->project_id,
                'concert_details_id' => $concertDetails->id,
                'name' => $source->name,
                'description' => $source->description,
            ]);

            foreach ($source->sets as $sourceSet) {
                $concertSet = $concertSetlist->sets()->create([
                    'name' => $sourceSet->name,
                    'position' => $sourceSet->position,
                ]);

                foreach ($sourceSet->items as $sourceItem) {
                    $concertSet->items()->create([
                        'song_id' => $sourceItem->song_id,
                        'position' => $sourceItem->position,
                        'key' => $sourceItem->key,
                        'notes' => $sourceItem->notes,
                    ]);
                }
            }

            return $concertSetlist->load('sets.items.song');
        });
    }
}
