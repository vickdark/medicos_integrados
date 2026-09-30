<?php

namespace App\Console\Commands;

use App\Actions\Payments\OpenAppointmentCharge;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Console\Command;

class OpenPendingCharges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:open-charges';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea el pago pendiente de las citas confirmadas o completadas que aún no tienen ningún pago';

    /**
     * Execute the console command.
     */
    public function handle(OpenAppointmentCharge $openCharge): int
    {
        $opened = 0;

        Appointment::query()
            ->whereIn('status', [AppointmentStatus::Confirmed, AppointmentStatus::Completed])
            ->with('doctor.user')
            ->lazyById(200)
            ->each(function (Appointment $appointment) use ($openCharge, &$opened): void {
                if ($openCharge->open($appointment)) {
                    $opened++;
                }
            });

        $this->info("Pagos pendientes creados: {$opened}.");

        return self::SUCCESS;
    }
}
