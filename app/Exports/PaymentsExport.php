<?php

namespace App\Exports;

use App\Enums\PaymentStatus;
use App\Models\Payment;

class PaymentsExport extends TableExport
{
    public function title(): string
    {
        return 'Pagos';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Fecha de pago', 'Paciente', 'Documento', 'Concepto', 'Método', 'Referencia', 'Estado', 'Monto'];
    }

    /**
     * @return list<int>
     */
    public function moneyColumns(): array
    {
        return [7];
    }

    /**
     * @return iterable<int, list<string|int|float|null>>
     */
    public function rows(): iterable
    {
        foreach ($this->query->with('patient')->lazy(500) as $payment) {
            /** @var Payment $payment */
            yield [
                $payment->paid_at?->format('d/m/Y'),
                $payment->patient->full_name,
                $payment->patient->document_number,
                $payment->concept,
                $payment->method->label(),
                $payment->reference,
                $payment->status->label(),
                (float) $payment->amount,
            ];
        }
    }

    /**
     * @return list<string>
     */
    public function filterSummary(): array
    {
        $status = PaymentStatus::tryFrom((string) ($this->filters['status'] ?? ''));

        return [
            ...parent::filterSummary(),
            ...($status ? ["Estado: {$status->label()}"] : []),
        ];
    }
}
