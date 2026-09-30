<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\StoreConsultationAddendumRequest;
use App\Models\AuditLog;
use App\Models\Consultation;
use Illuminate\Http\RedirectResponse;

class ConsultationAddendumController extends Controller
{
    /**
     * Add a clarifying note to the consultation. The original record is left
     * untouched and the note keeps its author, date and reason.
     */
    public function store(StoreConsultationAddendumRequest $request, Consultation $consultation): RedirectResponse
    {
        $addendum = $consultation->addenda()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'author_name' => $request->user()->name,
        ]);

        AuditLog::record(
            AuditAction::Created,
            $consultation,
            "Agregó una nota aclaratoria a la consulta ({$addendum->section->label()})",
            $consultation->patient,
        );

        return back()->with('success', 'Nota aclaratoria agregada. El registro original se conserva sin cambios.');
    }
}
