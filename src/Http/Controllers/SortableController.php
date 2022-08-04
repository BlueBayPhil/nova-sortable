<?php

namespace Ofcold\NovaSortable\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class SortableController extends Controller
{
	public function store(NovaRequest $request)
	{
		$model = get_class($request->newResource()->model());

		$items = json_decode(base64_decode($request->items));

		foreach ($items as $item) {
			tap($model::find($item->id), function($entry) use ($model, $item) {
                if($item->viaManyToMany) {
                    /** @var \Illuminate\Database\Eloquent\Model $baseItem */
                    $baseItem = $entry->{$item->viaResource}()
                                      ->get()
                        ->filter(fn($i) => $i->{$i->getKeyName()} == $item->viaResourceId)
                        ->first();
                    $baseItem->{$item->viaRelationship}()->updateExistingPivot($item->id, [
                        $model::orderColumnName() => $item->sort_order
                    ]);
                } else {
                    $entry->{$model::orderColumnName()} = $item->sort_order;
                }
			})->save();
		}

		return response()->json();
	}

}
