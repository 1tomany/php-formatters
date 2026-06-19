<?php

namespace OneToMany\Formatters\Number;

use function bcdiv;
use function is_numeric;
use function number_format;
use function round;

/**
 * @return ($value is null ? null : numeric-string)
 */
function format_decimal(int|float|string|null $value, int $scale = 2): ?string
{
    if (!is_numeric($value)) {
        return null;
    }

    return bcdiv(number_format(round((float) $value, $scale), $scale, '.', ''), '1', $scale);
}
