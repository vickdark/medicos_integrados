/**
 * Calendar chip colors by appointment status.
 */
export const appointmentTones: Record<string, string> = {
    requested:
        'border-amber-500 bg-amber-100 text-amber-900 dark:bg-amber-500/20 dark:text-amber-200',
    confirmed:
        'border-sky-500 bg-sky-100 text-sky-900 dark:bg-sky-500/20 dark:text-sky-200',
    completed:
        'border-emerald-500 bg-emerald-100 text-emerald-900 dark:bg-emerald-500/20 dark:text-emerald-200',
    cancelled:
        'border-neutral-400 bg-neutral-200 text-neutral-600 line-through dark:bg-neutral-500/20 dark:text-neutral-400',
};

export const appointmentDots: Record<string, string> = {
    requested: 'bg-amber-500',
    confirmed: 'bg-sky-500',
    completed: 'bg-emerald-500',
    cancelled: 'bg-neutral-400',
};
