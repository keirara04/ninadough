<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeSlotResource;
use App\Models\PreorderDate;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class TimeSlotController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = Validator::make($request->all(), [
            'date' => ['required', 'date_format:Y-m-d'],
            'fulfilment_method' => ['nullable', 'string', 'in:pickup,delivery'],
        ])->validate();

        $preorderDate = PreorderDate::whereDate('order_date', $validated['date'])->first();

        if (! $preorderDate) {
            return TimeSlotResource::collection(collect());
        }

        $slots = TimeSlot::query()
            ->where('preorder_date_id', $preorderDate->id)
            ->where('is_active', true)
            ->when(
                $request->filled('fulfilment_method'),
                fn ($query) => $query->whereIn('fulfilment_method', [$validated['fulfilment_method'], 'both']),
            )
            ->orderBy('starts_at')
            ->get();

        return TimeSlotResource::collection($slots);
    }
}
