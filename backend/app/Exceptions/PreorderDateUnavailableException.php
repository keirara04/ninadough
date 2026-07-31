<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreorderDateUnavailableException extends Exception
{
    protected $message = 'This preorder date is not available for the selected fulfilment method.';

    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
