<?php

namespace OneToMany\Formatters\Address;

use Symfony\Component\String\UnicodeString;

use function array_map;
use function hash;
use function is_numeric;
use function max;
use function min;
use function OneToMany\Formatters\Number\format_decimal;
use function Symfony\Component\String\u;
use function vsprintf;

/**
 * @return ?non-empty-string
 */
function format_address(
    ?string $street = null,
    ?string $unit = null,
    ?string $city = null,
    ?string $zip = null,
    ?string $state = null,
): ?string {
    if (
        null === $street
        && null === $unit
        && null === $city
        && null === $zip
        && null === $state
    ) {
        return null;
    }

    $addressComponentMapper = static function (?string $s): UnicodeString {
        return u((string) $s)->collapseWhitespace()->replace(',', '')->trim();
    };

    [
        $street,
        $unit,
        $city,
        $zip,
        $state,
    ] = array_map($addressComponentMapper, [
        $street, $unit, $city, $zip, $state,
    ]);

    // Combine street address and unit
    $line1 = $street->append(' ', $unit)->trim();

    if ($line1->isEmpty()) {
        return null;
    }

    // Combine state and ZIP code
    $line2 = $state->append(' ', $zip)->trim();

    // Add the city to the state and ZIP code
    if (!$city->isEmpty() && !$line2->isEmpty()) {
        $line2 = $city->append(', ', $line2);
    }

    if ($line2->isEmpty()) {
        $line2 = clone $city;
    }

    // Assume there are two lines by default
    $address = $line1->append(', ', $line2);

    if ($line2->isEmpty()) {
        $address = $line1;
    }

    return $address->trim(',')->toString() ?: null;
}

/**
 * @return ?non-empty-lowercase-string
 */
function hash_address(
    ?string $street = null,
    ?string $unit = null,
    ?string $city = null,
    ?string $zip = null,
    ?string $state = null,
): ?string {
    if (
        null === $street
        && null === $unit
        && null === $city
        && null === $zip
        && null === $state
    ) {
        return null;
    }

    $hashBits = [];

    // Create and lowercase each component of the address
    $creator = static function (?string $s): UnicodeString {
        return u((string) $s)->collapseWhitespace()->lower();
    };

    foreach ([$street, $unit, $city, $zip, $state] as $bit) {
        $hashBits[] = $creator($bit)->replace(' ', '');
    }

    // Remove commas, colons, and whitespace for each component
    $cleaner = static function (UnicodeString $s): UnicodeString {
        return $s->replace(',', '')->replace(':', '')->trim();
    };

    foreach ($hashBits as $idx => $bit) {
        $hashBits[$idx] = $cleaner($bit);
    }

    try {
        if (!u('')->join($hashBits)->isEmpty()) {
            return hash('crc32b', u(':')->join($hashBits));
        }
    } catch (\Throwable) {
    }

    return null;
}

/**
 * @param int<0, 10> $scale
 *
 * @return ?non-empty-string
 */
function format_coordinates(
    int|float|string|null $latitude,
    int|float|string|null $longitude,
    int $scale = 7,
): ?string {
    if (
        is_numeric($latitude)
        && is_numeric($longitude)
    ) {
        $scale = min(max(0, $scale), 10);

        return vsprintf('[%s,%s]', [
            format_decimal($latitude, $scale),
            format_decimal($longitude, $scale),
        ]);
    }

    return null;
}
