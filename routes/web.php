<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ConsultationAttachmentController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\ConsultationPrescriptionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorAvailabilityController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DoctorScheduleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientHistoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SendConsultationPrescriptionController;
use App\Http\Controllers\SpecialtyController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('patients/export', [PatientController::class, 'export'])->name('patients.export');
    Route::get('appointments/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');
    Route::get('appointments/export', [AppointmentController::class, 'export'])->name('appointments.export');
    Route::get('payments/export', [PaymentController::class, 'export'])->name('payments.export');
    Route::get('doctors/export', [DoctorController::class, 'export'])->name('doctors.export');
    Route::get('specialties/export', [SpecialtyController::class, 'export'])->name('specialties.export');
    Route::get('audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');

    Route::get('patients/{patient}/clinical-history', PatientHistoryController::class)->name('patients.history');

    Route::resource('patients', PatientController::class)->except(['destroy']);

    Route::get('patients/{patient}/consultations/create', [ConsultationController::class, 'create'])->name('consultations.create');
    Route::post('patients/{patient}/consultations', [ConsultationController::class, 'store'])->name('consultations.store');
    Route::get('consultations/{consultation}', [ConsultationController::class, 'show'])->name('consultations.show');
    Route::get('consultations/{consultation}/prescription', ConsultationPrescriptionController::class)->name('consultations.prescription');
    Route::post('consultations/{consultation}/prescription/email', SendConsultationPrescriptionController::class)->name('consultations.prescription.email');

    Route::resource('appointments', AppointmentController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');

    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('payments/{payment}/invoice', [PaymentController::class, 'invoice'])->name('payments.invoice');
    Route::patch('payments/{payment}/paid', [PaymentController::class, 'markPaid'])->name('payments.paid');

    Route::post('consultations/{consultation}/attachments', [ConsultationAttachmentController::class, 'store'])->name('attachments.store');
    Route::get('attachments/{attachment}', [ConsultationAttachmentController::class, 'show'])->name('attachments.show');
    Route::delete('attachments/{attachment}', [ConsultationAttachmentController::class, 'destroy'])->name('attachments.destroy');

    Route::resource('doctors', DoctorController::class)->only(['index', 'create', 'store']);

    Route::get('doctors/{doctor}/schedules', [DoctorScheduleController::class, 'index'])->name('schedules.index');
    Route::post('doctors/{doctor}/schedules', [DoctorScheduleController::class, 'store'])->name('schedules.store');
    Route::patch('doctors/{doctor}/slot', [DoctorScheduleController::class, 'updateSlot'])->name('schedules.slot');
    Route::get('doctors/{doctor}/availability', DoctorAvailabilityController::class)->name('doctors.availability');
    Route::delete('schedules/{schedule}', [DoctorScheduleController::class, 'destroy'])->name('schedules.destroy');

    Route::resource('specialties', SpecialtyController::class)->except(['show']);

    Route::post('tour', [TourController::class, 'store'])->name('tour.store');

    Route::patch('users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status');
    Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);

    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});

require __DIR__.'/settings.php';
