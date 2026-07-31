<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PreorderDateResource;
use App\Models\PreorderDate;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PreorderDateController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $dates = PreorderDate::query()
            ->whereDate('order_date', '>=', now()->toDateString())
            ->orderBy('order_date')
            ->get();

        return PreorderDateResource::collection($dates);
    }
}
