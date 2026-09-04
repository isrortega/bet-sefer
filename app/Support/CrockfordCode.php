<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Crockford base32 codes with a check digit.
 *
 * A "body" is 8 characters: 7 random characters from the Crockford alphabet
 * plus a trailing check digit. The check digit is
 *   (Σ value_i × weight_i) mod 32     with weights alternating 1, 3 (1 first)
 * mapped back onto a Crockford symbol. Prefixes are human-facing only:
 * copy codes use "BS-", loan receipts use "LN-", member codes have no prefix.
 */
final class CrockfordCode
{
    public const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public const BODY_LENGTH = 8;

    private function __construct() {}

    public static function generate(int $randomLength = 7): string
    {
        if ($randomLength < 1) {
            throw new InvalidArgumentException('randomLength must be positive');
        }

        $random = '';
        $bytes = random_bytes($randomLength * 2);

        for ($i = 0; $i < $randomLength; $i++) {
            $random .= self::ALPHABET[ord($bytes[$i]) % 32];
        }

        return $random.self::checkDigit($random);
    }

    public static function withPrefix(string $prefix, int $randomLength = 7): string
    {
        return $prefix.'-'.self::generate($randomLength);
    }

    /** Validate a full body (no prefix), returning whether it is structurally sound. */
    public static function isValidBody(string $body): bool
    {
        if (strlen($body) !== self::BODY_LENGTH) {
            return false;
        }

        $body = strtoupper($body);
        $random = substr($body, 0, -1);

        foreach (str_split($body) as $char) {
            if (strpos(self::ALPHABET, $char) === false) {
                return false;
            }
        }

        return self::checkDigit($random) === substr($body, -1);
    }

    /** Validate a full code that carries an optional prefix, e.g. "BS-4F7K2Q91". */
    public static function isValid(string $code, ?string $prefix = null): bool
    {
        if ($prefix !== null) {
            $expected = $prefix.'-';

            if (! str_starts_with(strtoupper($code), strtoupper($expected))) {
                return false;
            }

            $body = substr($code, strlen($expected));

            return self::isValidBody($body);
        }

        return self::isValidBody($code);
    }

    public static function checkDigit(string $random): string
    {
        $sum = 0;

        foreach (str_split(strtoupper($random)) as $i => $char) {
            $value = strpos(self::ALPHABET, $char);

            if ($value === false) {
                throw new InvalidArgumentException("Invalid Crockford character: {$char}");
            }

            $weight = ($i % 2 === 0) ? 1 : 3;
            $sum += $value * $weight;
        }

        return self::ALPHABET[$sum % 32];
    }
}
