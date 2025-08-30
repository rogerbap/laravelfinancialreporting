<?php
// app/Rules/ValidCategoryCode.php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Category;

class ValidCategoryCode implements Rule
{
    public function passes($attribute, $value): bool
    {
        // Check if code exists and is active
        return Category::where('code', strtoupper($value))
            ->where('is_active', true)
            ->exists();
    }

    public function message(): string
    {
        return 'The selected category code is invalid or inactive.';
    }
}