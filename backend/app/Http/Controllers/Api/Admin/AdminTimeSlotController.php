<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeSlotResource;
use App\Models\PreorderDate;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminTimeSlotController extends Controller
{
    public function index(PreorderDate $preorderDate)
    {
        return TimeSlotResource::collection(
            $preorderDate->timeSlots()->orderBy('starts_at')->get()
        );
    }

    public function store(Request $request, PreorderDate $preorderDate)
    {
        $validated = $this->validated($request);

        $slot = $preorderDate->timeSlots()->create($validated);

        return new TimeSlotResource($slot);
    }

    public function update(Request $request, PreorderDate $preorderDate, TimeSlot $timeSlot)
    {
        abort_if($timeSlot->preorder_date_id !== $preorderDate->id, 404);

        $validated = $this->validated($request, isUpdate: true);

        $timeSlot->update($validated);

        return new TimeSlotResource($timeSlot->fresh());
    }

    public function destroy(PreorderDate $preorderDate, TimeSlot $timeSlot)
    {
        abort_if($timeSlot->preorder_date_id !== $preorderDate->id, 404);

        $timeSlot->update(['is_active' => false]);

        return response()->json(['data' => ['ok' => true]]);
    }

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return Validator::make($request->all(), [
            'label' => [$required, 'string', 'max:60'],
            'starts_at' => [$required, 'date_format:H:i'],
            'ends_at' => [$required, 'date_format:H:i', 'after:starts_at'],
            'fulfilment_method' => [$required, 'string', 'in:pickup,delivery,both'],
            'capacity_limit' => [$required, 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ])->validate();
    }
}
