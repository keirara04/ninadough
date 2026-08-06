<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminPreorderDateResource;
use App\Models\PreorderDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminPreorderDateController extends Controller
{
    private const MAX_PER_PAGE = 50;

    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', self::MAX_PER_PAGE), 1), self::MAX_PER_PAGE);

        return AdminPreorderDateResource::collection(
            PreorderDate::query()->orderBy('order_date')->paginate($perPage)->withQueryString(),
        );
    }

    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'order_date' => ['required', 'date_format:Y-m-d', 'unique:preorder_dates,order_date'],
            'cutoff_at' => ['required', 'date'],
            'capacity_limit' => ['required', 'integer', 'min:1'],
            'pickup_enabled' => ['boolean'],
            'delivery_enabled' => ['boolean'],
            'status' => ['nullable', 'string', 'in:open,closed,full'],
            'note_internal' => ['nullable', 'string', 'max:500'],
        ])->validate();

        $date = PreorderDate::create([
            ...$validated,
            'status' => $validated['status'] ?? 'open',
            'created_by_user_id' => $request->user()->id,
        ]);

        return new AdminPreorderDateResource($date);
    }

    public function update(Request $request, PreorderDate $preorderDate)
    {
        $validated = Validator::make($request->all(), [
            'cutoff_at' => ['sometimes', 'date'],
            'capacity_limit' => ['sometimes', 'integer', 'min:1'],
            'pickup_enabled' => ['sometimes', 'boolean'],
            'delivery_enabled' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', 'in:open,closed,full'],
            'note_internal' => ['nullable', 'string', 'max:500'],
        ])->validate();

        $preorderDate->update($validated);

        return new AdminPreorderDateResource($preorderDate->fresh());
    }
}
