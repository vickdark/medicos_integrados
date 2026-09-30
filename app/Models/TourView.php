<?php

namespace App\Models;

use Database\Factories\TourViewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records that a user already went through (or skipped) a guided tour on a
 * given device, so it is only shown again on a device they had not used.
 */
class TourView extends Model
{
    /** @use HasFactory<TourViewFactory> */
    use HasFactory;

    /**
     * The guided tour shown to patients on their first access from a device.
     */
    public const PATIENT_ONBOARDING = 'patient-onboarding';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'tour',
        'device_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
