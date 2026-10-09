<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const KEY_LONG_TERM_THRESHOLD = 'long_term_booking_threshold_days';
    public const DEFAULT_LONG_TERM_THRESHOLD = 90;

    public const KEY_PRICE_RECONFIRMATION_THRESHOLD = 'price_reconfirmation_threshold_days';
    public const DEFAULT_PRICE_RECONFIRMATION_THRESHOLD = 30;

    public const KEY_DOWNPAYMENT_PERCENTAGE = 'downpayment_percentage';
    public const DEFAULT_DOWNPAYMENT_PERCENTAGE = 50.0;

    /**
     * Indicates if the model has timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'key';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * Get a setting value by key.
     */
    public static function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function setSetting(string $key, mixed $value, string $type = 'string'): static
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }

    public static function getLongTermBookingThresholdDays(): int
    {
        return (int) static::getSetting(self::KEY_LONG_TERM_THRESHOLD, self::DEFAULT_LONG_TERM_THRESHOLD);
    }

    public static function getPriceReconfirmationThresholdDays(): int
    {
        return (int) static::getSetting(self::KEY_PRICE_RECONFIRMATION_THRESHOLD, self::DEFAULT_PRICE_RECONFIRMATION_THRESHOLD);
    }
}
