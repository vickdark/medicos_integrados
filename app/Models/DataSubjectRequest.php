<?php

namespace App\Models;

use App\Enums\DataRequestStatus;
use App\Enums\DataRequestType;
use Database\Factories\DataSubjectRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A request of a patient about their personal data (access, correction or
 * deletion), with the legal deadline the clinic has to answer it.
 */
class DataSubjectRequest extends Model
{
    /** @use HasFactory<DataSubjectRequestFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'user_id',
        'type',
        'details',
        'status',
        'due_at',
        'responded_at',
        'responded_by',
        'responded_by_name',
        'response',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DataRequestType::class,
            'status' => DataRequestStatus::class,
            'details' => 'encrypted',
            'response' => 'encrypted',
            'due_at' => 'date:Y-m-d',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * Open a request for the patient, due in the business days the law gives for its type.
     */
    public static function open(Patient $patient, User $user, DataRequestType $type, string $details): self
    {
        return self::query()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'type' => $type,
            'details' => $details,
            'due_at' => now()->startOfDay()->addWeekdays($type->responseBusinessDays()),
        ]);
    }

    /**
     * Whether the request is still waiting for an answer.
     */
    public function isPending(): bool
    {
        return $this->status === DataRequestStatus::Pending;
    }

    /**
     * Whether the legal deadline passed without an answer.
     */
    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_at->isBefore(today());
    }

    /**
     * Close the request with the answer given to the patient.
     */
    public function answer(DataRequestStatus $status, string $response, User $user): void
    {
        $this->update([
            'status' => $status,
            'response' => $response,
            'responded_at' => now(),
            'responded_by' => $user->id,
            'responded_by_name' => $user->name,
        ]);
    }

    /**
     * @param  Builder<DataSubjectRequest>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', DataRequestStatus::Pending);
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
