<?php

namespace App\Models;

use Database\Factories\MedicationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    /** @use HasFactory<MedicationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'presentation',
        'concentration',
        'description',
    ];

    /**
     * The text written on a prescription, e.g. "Paracetamol 500 mg · Tabletas".
     *
     * @return Attribute<string, never>
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => collect([
            trim($this->name.' '.$this->concentration),
            $this->presentation,
        ])->filter()->implode(' · '));
    }
}
