<?php

namespace App\Rules;

use App\Models\PreorderDate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Laravel's built-in `exists:` rule does a plain string equality check
 * against the column, which is unreliable for a `date`-cast column across
 * database drivers (storage format can include a time component depending
 * on the connection). whereDate() is the portable comparison.
 */
class PreorderDateExists implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! PreorderDate::whereDate('order_date', $value)->exists()) {
            $fail('The selected preorder date is invalid.');
        }
    }
}
