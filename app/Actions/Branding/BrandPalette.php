<?php

namespace App\Actions\Branding;

/**
 * Color helpers for the brand color: validation, contrast and the tints the PDFs
 * and Excel files need, since they cannot compute them like the browser does.
 */
class BrandPalette
{
    public const DEFAULT_COLOR = '#0D9488';

    /**
     * Lowest contrast against white the brand color may have, so white text on
     * brand-colored buttons and badges stays readable.
     */
    public const MIN_CONTRAST = 3.0;

    /**
     * Suggested colors shown as swatches in the settings.
     *
     * @var array<string, string>
     */
    public const PRESETS = [
        'Verde azulado' => '#0D9488',
        'Azul' => '#2563EB',
        'Índigo' => '#4F46E5',
        'Violeta' => '#7C3AED',
        'Rosa' => '#DB2777',
        'Rojo' => '#DC2626',
        'Naranja' => '#C2410C',
        'Verde' => '#15803D',
        'Pizarra' => '#475569',
    ];

    public static function isValid(string $color): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
    }

    public static function normalize(string $color): string
    {
        return strtoupper($color);
    }

    /**
     * WCAG contrast ratio of the color against white.
     */
    public static function contrastWithWhite(string $color): float
    {
        return 1.05 / (self::luminance($color) + 0.05);
    }

    public static function hasEnoughContrast(string $color): bool
    {
        return self::contrastWithWhite($color) >= self::MIN_CONTRAST;
    }

    /**
     * Mix the color with white; $amount is the share of the color that remains.
     */
    public static function tint(string $color, float $amount = 0.08): string
    {
        return self::mix($color, [255, 255, 255], $amount);
    }

    /**
     * Mix the color with black; $amount is the share of the color that remains.
     */
    public static function shade(string $color, float $amount = 0.6): string
    {
        return self::mix($color, [0, 0, 0], $amount);
    }

    /**
     * Color as the "RRGGBB" string spreadsheets expect.
     */
    public static function withoutHash(string $color): string
    {
        return ltrim(self::normalize($color), '#');
    }

    /**
     * @return array{brand: string, brandTint: string, brandDark: string}
     */
    public static function documentColors(string $color): array
    {
        return [
            'brand' => $color,
            'brandTint' => self::tint($color, 0.08),
            'brandDark' => self::shade($color, 0.6),
        ];
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function rgb(string $color): array
    {
        $hex = ltrim($color, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function luminance(string $color): float
    {
        [$red, $green, $blue] = array_map(function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, self::rgb($color));

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $other
     */
    private static function mix(string $color, array $other, float $amount): string
    {
        $base = self::rgb($color);

        return sprintf('#%02X%02X%02X', ...array_map(
            fn (int $channel, int $target): int => (int) round($channel * $amount + $target * (1 - $amount)),
            $base,
            $other,
        ));
    }
}
