<?php

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine whether the user can list payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Patient);
    }

    /**
     * Determine whether the user can see the details of the payment.
     */
    public function view(User $user, Payment $payment): bool
    {
        return match ($user->role) {
            UserRole::Admin, UserRole::Receptionist => true,
            UserRole::Patient => $payment->patient?->user_id === $user->id,
            UserRole::Doctor => false,
        };
    }

    /**
     * Determine whether the user can download the invoice of the payment. Only
     * paid payments are invoiced.
     */
    public function downloadInvoice(User $user, Payment $payment): bool
    {
        return $this->view($user, $payment) && $payment->status === PaymentStatus::Paid;
    }

    /**
     * Determine whether the user can record payments.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    /**
     * Determine whether the user can mark a pending payment as paid.
     */
    public function markPaid(User $user, Payment $payment): bool
    {
        return $this->create($user) && $payment->status === PaymentStatus::Pending;
    }
}
