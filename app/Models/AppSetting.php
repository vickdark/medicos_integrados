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

    /**
     * Privacy policy values the administrator can override; the rest of the
     * policy comes from config/privacy.php.
     *
     * @var list<string>
     */
    public const PRIVACY_FIELDS = ['company', 'nit', 'address', 'phone', 'contact_email', 'rnbd_registration', 'version', 'updated_at'];

    public const LANDING_CONTENT = 'landing_content';

    private const LANDING_CONTENT_CACHE_KEY = 'app_settings.landing_content';

    private const PRIVACY_CACHE_KEY = 'app_settings.privacy';

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
     * The editable texts of the landing page. What the administrator saved wins over
     * the defaults of config/landing.php.
     *
     * @return array<string, mixed>
     */
    public static function landingContent(): array
    {
        $defaults = (array) config('landing');

        try {
            $stored = Cache::rememberForever(
                self::LANDING_CONTENT_CACHE_KEY,
                fn (): array => (array) json_decode((string) self::query()->where('key', self::LANDING_CONTENT)->value('value'), true),
            );
        } catch (Throwable) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $stored);
    }

    /**
     * Save the texts of the landing page.
     *
     * @param  array<string, mixed>  $content
     */
    public static function setLandingContent(array $content): void
    {
        self::query()->updateOrCreate(
            ['key' => self::LANDING_CONTENT],
            ['value' => json_encode($content, JSON_UNESCAPED_UNICODE)],
        );

        Cache::forget(self::LANDING_CONTENT_CACHE_KEY);
    }

    /**
     * Go back to the original texts of the landing page.
     */
    public static function resetLandingContent(): void
    {
        self::query()->where('key', self::LANDING_CONTENT)->delete();

        Cache::forget(self::LANDING_CONTENT_CACHE_KEY);
    }

    /**
     * The personal data policy: its version and the data of the controller. What the
     * administrator saved wins over config/privacy.php, which holds the defaults.
     *
     * @return array{version: string, updated_at: string, company: string, nit: ?string, address: ?string, phone: ?string, contact_email: string, rnbd_registration: ?string}
     */
    public static function privacyPolicy(): array
    {
        $defaults = [
            'version' => (string) config('privacy.version'),
            'updated_at' => (string) config('privacy.updated_at'),
            'company' => (string) config('privacy.company'),
            'nit' => config('privacy.nit'),
            'address' => config('privacy.address'),
            'phone' => config('privacy.phone'),
            'contact_email' => (string) config('privacy.contact_email'),
            'rnbd_registration' => null,
        ];

        try {
            $stored = Cache::rememberForever(
                self::PRIVACY_CACHE_KEY,
                fn (): array => self::query()
                    ->where('key', 'like', 'privacy.%')
                    ->pluck('value', 'key')
                    ->mapWithKeys(fn (?string $value, string $key): array => [substr($key, strlen('privacy.')) => $value])
                    ->filter(fn (?string $value): bool => filled($value))
                    ->all(),
            );
        } catch (Throwable) {
            return $defaults;
        }

        return array_merge($defaults, array_intersect_key($stored, $defaults));
    }

    /**
     * The version of the personal data policy patients have to accept.
     */
    public static function privacyVersion(): string
    {
        return self::privacyPolicy()['version'];
    }

    /**
     * Save the data of the controller shown in the policy. A blank value goes back
     * to the default.
     *
     * @param  array<string, string|null>  $values
     */
    public static function savePrivacyController(array $values): void
    {
        foreach (array_intersect_key($values, array_flip(['company', 'nit', 'address', 'phone', 'contact_email', 'rnbd_registration'])) as $field => $value) {
            self::setPrivacyField($field, $value);
        }

        Cache::forget(self::PRIVACY_CACHE_KEY);
    }

    /**
     * Publish a new major version of the policy, so every patient has to accept it again.
     */
    public static function publishPrivacyVersion(): string
    {
        $version = ((int) explode('.', self::privacyVersion())[0] + 1).'.0';

        self::setPrivacyField('version', $version);
        self::setPrivacyField('updated_at', today()->toDateString());

        Cache::forget(self::PRIVACY_CACHE_KEY);

        return $version;
    }

    private static function setPrivacyField(string $field, ?string $value): void
    {
        if (blank($value)) {
            self::query()->where('key', "privacy.{$field}")->delete();

            return;
        }

        self::query()->updateOrCreate(['key' => "privacy.{$field}"], ['value' => trim($value)]);
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
