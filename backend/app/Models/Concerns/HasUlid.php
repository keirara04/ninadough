<?php
// backend/app/Models/Concerns/HasUlid.php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

trait HasUlid
{
    use HasUlids;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }
}
