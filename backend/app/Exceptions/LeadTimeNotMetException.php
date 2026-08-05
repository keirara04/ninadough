<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadTimeNotMetException extends Exception
{
    public function __construct(private readonly int $leadTimeDays)
    {
        parent::__construct("One or more items in your cart need at least {$leadTimeDays} day(s) lead time. Please choose a later date.");
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
