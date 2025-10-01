<?php

/**
 * Example usage of OembedExtractor in a controller
 * 
 * This example shows how to integrate the OembedExtractor service
 * into a controller, similar to how EmbedExtractor is used in SeriesController.
 */

namespace App\Http\Controllers;

use App\Services\Embeds\OembedExtractor;
use App\Models\Series;
use Illuminate\Http\Request;

class ExampleOembedUsageController extends Controller
{
    /**
     * Load the embeds using oEmbed API and add to the UI
     *
     * @throws \Throwable
     */
    public function loadOembeds(int $id, OembedExtractor $oembedExtractor, Request $request)
    {
        // load the series
        if (!$series = Series::find($id)) {
            flash()->error('Error', 'No such series');
            return back();
        }

        // extract all the links from the series using oEmbed API
        $oembedExtractor->setLayout("medium");
        $embeds = $oembedExtractor->getEmbedsForSeries($series);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'Added oEmbed embeds to series page.',
                'Success' => view('embeds.playlist')
                    ->with(compact('embeds'))
                    ->render(),
            ];
        }
        
        flash()->success('Error', 'You cannot load embeds directly');
        return back();
    }

    /**
     * Load minimal embeds using oEmbed API
     *
     * @throws \Throwable
     */
    public function loadMinimalOembeds(int $id, OembedExtractor $oembedExtractor, Request $request)
    {
        // load the series
        if (!$series = Series::find($id)) {
            flash()->error('Error', 'No such series');
            return back();
        }

        // extract all the links using oEmbed API with small size
        $oembedExtractor->setLayout("small");
        $embeds = $oembedExtractor->getEmbedsForSeries($series);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'Added minimal oEmbed embeds to series page.',
                'Success' => view('embeds.minimal-playlist')
                    ->with(compact('embeds'))
                    ->render(),
            ];
        }
        
        flash()->success('Error', 'You cannot load embeds directly');
        return back();
    }
}
