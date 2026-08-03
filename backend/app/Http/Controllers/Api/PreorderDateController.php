<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PreorderDateResource;
use App\Models\PreorderDate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PreorderDateController extends Controller
{
    private const MAX_PER_PAGE = 50;

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', self::MAX_PER_PAGE), 1), self::MAX_PER_PAGE);

        $dates = PreorderDate::query()
            ->whereDate('order_date', '>=', now()->toDateString())
            ->orderBy('order_date')
            ->paginate($perPage)
            ->withQueryString();

        return PreorderDateResource::collection($dates);
    }
}
