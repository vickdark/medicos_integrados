const locale = 'es';

export function startOfDay(date: Date): Date {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

export function addDays(date: Date, days: number): Date {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
}

export function addMonths(date: Date, months: number): Date {
    const target = new Date(date.getFullYear(), date.getMonth() + months, 1);
    const lastDay = new Date(
        target.getFullYear(),
        target.getMonth() + 1,
        0,
    ).getDate();

    return new Date(
        target.getFullYear(),
        target.getMonth(),
        Math.min(date.getDate(), lastDay),
    );
}

/**
 * Monday of the week that contains the given date.
 */
export function startOfWeek(date: Date): Date {
    const offset = (date.getDay() + 6) % 7;

    return addDays(startOfDay(date), -offset);
}

export function isSameDay(a: Date, b: Date): boolean {
    return (
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
    );
}

/**
 * Local calendar date as "YYYY-MM-DD" (no timezone conversion).
 */
export function toISODate(date: Date): string {
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

/**
 * First day of the six-week grid that shows the month of the given date.
 * Every week of that month, and the week of any selected day in it, fits in the grid.
 */
export function monthGridStart(date: Date): Date {
    return startOfWeek(new Date(date.getFullYear(), date.getMonth(), 1));
}

export const MONTH_GRID_DAYS = 42;

export function monthGridDays(date: Date): Date[] {
    const start = monthGridStart(date);

    return Array.from({ length: MONTH_GRID_DAYS }, (_, index) =>
        addDays(start, index),
    );
}

export function weekDays(date: Date): Date[] {
    const start = startOfWeek(date);

    return Array.from({ length: 7 }, (_, index) => addDays(start, index));
}

export function minutesOfDay(date: Date): number {
    return date.getHours() * 60 + date.getMinutes();
}

export function formatLongDay(date: Date): string {
    const text = date.toLocaleDateString(locale, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    return text.charAt(0).toUpperCase() + text.slice(1);
}

export function formatMonthTitle(date: Date): string {
    const text = date.toLocaleDateString(locale, {
        month: 'long',
        year: 'numeric',
    });

    return text.charAt(0).toUpperCase() + text.slice(1);
}

export function formatWeekRange(date: Date): string {
    const days = weekDays(date);
    const first = days[0];
    const last = days[6];
    const short = (value: Date) =>
        value.toLocaleDateString(locale, { day: 'numeric', month: 'short' });

    return `${short(first)} – ${short(last)} ${last.getFullYear()}`;
}

export function weekdayShort(date: Date): string {
    return date.toLocaleDateString(locale, { weekday: 'short' });
}

export type Positioned<T> = { item: T; column: number; columns: number };

/**
 * Lay out overlapping intervals side by side. Each item gets a column index
 * and the number of columns of its overlapping group.
 */
export function layoutOverlaps<T>(
    items: T[],
    start: (item: T) => number,
    end: (item: T) => number,
): Positioned<T>[] {
    const sorted = [...items].sort((a, b) => start(a) - start(b));
    const result: Positioned<T>[] = [];
    let group: Positioned<T>[] = [];
    let columnEnds: number[] = [];
    let groupEnd = -1;

    const flush = () => {
        group.forEach((entry) => (entry.columns = columnEnds.length));
        result.push(...group);
        group = [];
        columnEnds = [];
    };

    for (const item of sorted) {
        if (group.length > 0 && start(item) >= groupEnd) {
            flush();
        }

        let column = columnEnds.findIndex(
            (columnEnd) => columnEnd <= start(item),
        );

        if (column === -1) {
            column = columnEnds.length;
            columnEnds.push(0);
        }

        columnEnds[column] = end(item);
        groupEnd = Math.max(groupEnd, end(item));
        group.push({ item, column, columns: 1 });
    }

    flush();

    return result;
}
