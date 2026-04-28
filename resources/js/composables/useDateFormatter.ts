const dateTimeFormatter = (options: Intl.DateTimeFormatOptions) =>
    new Intl.DateTimeFormat('en', options);

export const formatDate = (value: string | null): string => {
    if (!value) return 'No due date';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'No due date';

    return dateTimeFormatter({ month: 'short', day: 'numeric' }).format(date);
};

export const formatDateTime = (value: string | null): string => {
    if (!value) return 'No date';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'No date';

    return dateTimeFormatter({
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};
