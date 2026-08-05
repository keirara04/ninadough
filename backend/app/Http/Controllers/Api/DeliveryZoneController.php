<?php

namespace App\Http\Controllers\Api;

use App\Actions\Checkout\ResolveDeliveryZoneAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\DeliveryZoneResource;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class DeliveryZoneController extends Controller
{
    private const MAX_PER_PAGE = 50;

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', self::MAX_PER_PAGE), 1), self::MAX_PER_PAGE);

        $zones = DeliveryZone::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->paginate($perPage)
            ->withQueryString();

        return DeliveryZoneResource::collection($zones);
    }

    public function lookup(Request $request, ResolveDeliveryZoneAction $resolveDeliveryZoneAction): DeliveryZoneResource
    {
        $validated = Validator::make($request->all(), [
            'postcode' => ['required', 'string', 'max:20'],
        ])->validate();

        $zone = $resolveDeliveryZoneAction->execute($validated['postcode']);

        return new DeliveryZoneResource($zone);
    }
}
