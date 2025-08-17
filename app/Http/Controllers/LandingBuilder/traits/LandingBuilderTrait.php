<?php
namespace App\Http\Controllers\LandingBuilder\traits;

use App\Models\Landing;
use Illuminate\Http\Request;

trait LandingBuilderTrait
{

    private function getPanelCommonData(Request $request): array
    {
        $landingItems = Landing::query()
            ->withCount([
                'components'
            ])
            ->get();

        return [
            'landingItems' => $landingItems,
        ];
    }

}
