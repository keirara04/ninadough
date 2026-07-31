<?php

namespace App\Http\Resources;

use App\DataTransferObjects\CartQuote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartQuoteResource extends JsonResource
{
    public function __construct(private readonly CartQuote $quote)
    {
        parent::__construct($quote);
    }

    public function toArray(Request $request): array
    {
        return $this->quote->toArray();
    }
}
