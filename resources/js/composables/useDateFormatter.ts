import { format, formatDistanceToNowStrict } from 'date-fns';

const toDate = (value: string | Date | null): Date | null => {
    if (!value) return null;

    const date = typeof value === 'string' ? new Date(value) : value;
    if (Number.isNaN(date.getTime())) return null;

    return date;
};

/**
 * Format a date for display.
 */
export const formatDate = (value: string | null): string => {
    const date = toDate(value);
    if (!date) return 'No due date';

    return format(date, 'MMM d, yyyy');
};

/**
 * Format a date and time for display.
 */
export const formatDateTime = (value: string | null): string => {
    const date = toDate(value);
    if (!date) return 'No date';

    return format(date, 'MMM d, yyyy, HH:mm');
};

/**
 * Format a short date string (e.g. "Mon, Sep 10").
 */
export const formatShortDate = (value: string | Date | null): string => {
    const date = toDate(value);
    if (!date) return '';

    return format(date, 'EEE, MMM d');
};

/**
 * Format a short time string (e.g. "14:30").
 */
export const formatShortTime = (value: string | Date | null): string => {
    const date = toDate(value);
    if (!date) return '';

    return format(date, 'HH:mm');
};

/**
 * Format a date-time range (e.g. "Sep 10, 14:00 - 15:00").
 * Returns null when the start value is missing or invalid.
 */
export const formatDateTimeRange = (
    start: string | null,
    end?: string | null,
): string | null => {
    const startDate = toDate(start);
    if (!startDate) return null;

    const startStr = format(startDate, 'MMM d, HH:mm');

    if (!end) return startStr;

    const endDate = toDate(end);
    if (!endDate) return startStr;

    return `${startStr} - ${format(endDate, 'MMM d, HH:mm')}`;
};

/**
 * Return a human-readable relative time string (e.g. "5 minutes ago", "1 day ago").
 */
export const relativeTime = (value: string): string => {
    const date = toDate(value);
    if (!date) return '';
    return formatDistanceToNowStrict(date, { addSuffix: true });
};

//  Input helpers (date / time pickers)

/**
 * Convert a date to a value suitable for a date input.
 */
export const toDateInputValue = (value: string | Date | null): string => {
    const date = toDate(value);
    if (!date) return '';

    return format(date, 'yyyy-MM-dd');
};

/**
 * Convert a date to a value suitable for a time input.
 */
export const toTimeInputValue = (value: string | Date | null): string => {
    const date = toDate(value);
    if (!date) return '';

    return format(date, 'HH:mm');
};

/**
 * Convert a date and time input values to an ISO 8601 string.
 */
export const fromDatetimeParts = (date: string, time: string): string => {
    if (!date || !time) return '';

    const parsed = new Date(`${date}T${time}:00`);
    if (Number.isNaN(parsed.getTime())) return '';

    return parsed.toISOString();
};

/**
 * Return the current time as an ISO 8601 string.
 */
export const nowIso = (): string => new Date().toISOString();

/**
 * Return a "HH:mm" scroll-time value offset minutes before now, clamped to
 * midnight, so that the now-indicator is visible with context above it in
 * FullCalendar time-grid views.
 */
export const scrollTimeBeforeNow = (offsetMinutes = 120): string => {
    const now = new Date();
    const scrollDate = new Date(now.getTime() - offsetMinutes * 60 * 1000);
    // Clamp to midnight when the offset crosses into the previous day
    if (scrollDate.getDate() !== now.getDate()) {
        scrollDate.setHours(0, 0, 0, 0);
    }
    return toTimeInputValue(scrollDate);
};
