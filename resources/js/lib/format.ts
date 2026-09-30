const locale = 'es';

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date =
        value.length === 10 ? new Date(`${value}T00:00:00`) : new Date(value);

    return date.toLocaleDateString(locale, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

/**
 * Format hours and minutes (0-23, 0-59) as a 12-hour clock, e.g. "2:30 PM".
 */
export function formatClock(hours: number, minutes: number): string {
    const period = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 === 0 ? 12 : hours % 12;

    return `${hour12}:${String(minutes).padStart(2, '0')} ${period}`;
}

/**
 * Convert a 24-hour "HH:mm" value to a 12-hour label, e.g. "14:30" -> "2:30 PM".
 */
export function formatTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const [hours, minutes] = value.split(':').map(Number);

    return formatClock(hours, minutes);
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    return `${formatDate(value)}, ${formatClock(date.getHours(), date.getMinutes())}`;
}

export function formatFileSize(bytes: number): string {
    if (bytes < 1024 * 1024) {
        return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function formatMoney(value: string | number | null | undefined): string {
    return Number(value ?? 0).toLocaleString(locale, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}
