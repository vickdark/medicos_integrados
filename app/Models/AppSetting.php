<?php

namespace App\Models;

use App\Actions\Branding\BrandPalette;
use Database\Factories\AppSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Global application settings stored as key/value pairs, such as the brand color.
 */
class AppSetting extends Model
{
    /** @use HasFactory<AppSettingFactory> */
    use HasFactory;

    public const BRAND_COLOR = 'brand_color';

    public const LANDING_SHOW_DOCTORS = 'landing_show_doctors';

    private const BRAND_CACHE_KEY = 'app_settings.brand_color';

    private const LANDING_DOCTORS_CACHE_KEY = 'app_settings.landing_show_doctors';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * The accent color of the whole application as "#RRGGBB". It falls back to the
     * default when nothing was saved or the table does not exist yet.
     */
    public static function brandColor(): string
    {
        try {
            return Cache::rememberForever(self::BRAND_CACHE_KEY, function (): string {
                $stored = self::query()->where('key', self::BRAND_COLOR)->value('value');

                return BrandPalette::isValid((string) $stored)
                    ? BrandPalette::normalize((string) $stored)
                    : BrandPalette::DEFAULT_COLOR;
            });
        } catch (Throwable) {
            return BrandPalette::DEFAULT_COLOR;
        }
    }

    /**
     * Save the accent color of the whole application.
     */
    public static function setBrandColor(string $color): void
    {
        self::query()->updateOrCreate(
            ['key' => self::BRAND_COLOR],
            ['value' => BrandPalette::normalize($color)],
        );

        Cache::forget(self::BRAND_CACHE_KEY);
    }

    /**
     * Go back to the default accent color.
     */
    public static function resetBrandColor(): void
    {
        self::query()->where('key', self::BRAND_COLOR)->delete();

        Cache::forget(self::BRAND_CACHE_KEY);
    }

    /**
     * Whether the landing page shows the doctors of the clinic. Off by default: the
     * administrator has to enable it.
     */
    public static function showDoctorsOnLanding(): bool
    {
        try {
            return Cache::rememberForever(
                self::LANDING_DOCTORS_CACHE_KEY,
                fn (): bool => self::query()->where('key', self::LANDING_SHOW_DOCTORS)->value('value') === '1',
            );
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Enable or disable the public list of doctors on the landing page.
     */
    public static function setShowDoctorsOnLanding(bool $show): void
    {
        self::query()->updateOrCreate(
            ['key' => self::LANDING_SHOW_DOCTORS],
            ['value' => $show ? '1' : '0'],
        );

        Cache::forget(self::LANDING_DOCTORS_CACHE_KEY);
    }
}
