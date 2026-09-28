import type { CalendarApi as FullCalendarApi } from '@fullcalendar/vue3';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const STORAGE_KEY = 'taskly_calendar_view';

const VIEW_ALIASES: Record<string, string> = {
    month: 'dayGridMonth',
    week: 'timeGridWeek',
    day: 'timeGridDay',
};

const VIEW_ALIASES_REVERSE: Record<string, string> = Object.fromEntries(
    Object.entries(VIEW_ALIASES).map(([alias, fc]) => [fc, alias]),
);

const toUrlView = (view: string) => VIEW_ALIASES_REVERSE[view] ?? view;

const toFcView = (alias: string) => VIEW_ALIASES[alias] ?? alias;

/**
 * The visible range spills into the neighbouring months, so its bounds make a
 * poor anchor date — the September grid starts on 31 August. The middle of the
 * range belongs to the requested period in every view.
 */
const rangeCenter = (start?: string | null, end?: string | null) => {
    const from = new Date(start ?? '');
    if (Number.isNaN(from.getTime())) return null;

    const to = new Date(end ?? '');
    if (!end || Number.isNaN(to.getTime())) return from.toISOString();

    return new Date((from.getTime() + to.getTime()) / 2).toISOString();
};

interface CalendarViewOptions {
    view?: string | null;
    start?: string | null;
    end?: string | null;
    api: () => FullCalendarApi | undefined;
}

/**
 * Own the period shown by the grid: which view is active, which range is
 * loaded, and the `view`/`start`/`end` query string the URL keeps in sync with.
 */
export function useCalendarView({
    view,
    start,
    end,
    api,
}: CalendarViewOptions) {
    const page = usePage();

    const stored =
        typeof window !== 'undefined'
            ? localStorage.getItem(STORAGE_KEY)
            : null;

    const initialView = toFcView(view || stored || 'month');
    const initialDate = rangeCenter(start, end);

    const currentView = ref(initialView);
    const viewTitle = ref('');

    const prev = () => api()?.prev();
    const next = () => api()?.next();
    const today = () => api()?.today();
    const setView = (fcView: string) => api()?.changeView(fcView);

    let pending: Date | null = null;

    const focusPending = (): boolean => {
        const calendar = api();
        if (!calendar || !pending) return false;

        const date = pending;
        pending = null;

        const { activeStart, activeEnd } = calendar.view;
        if (date >= activeStart && date < activeEnd) return false;

        calendar.gotoDate(date);
        return true;
    };

    const focusOn = (value: string | Date) => {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return;

        pending = date;
        focusPending();
    };

    const loadRange = (fcView: string, from: string, to: string) => {
        const current = new URL(page.url, window.location.origin);

        router.get(
            '/calendar',
            {
                ...Object.fromEntries(current.searchParams),
                view: toUrlView(fcView),
                start: from,
                end: to,
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['events'],
                replace: true,
            },
        );
    };

    const onRangeChange = (
        from: string,
        to: string,
        fcView: string,
        title: string,
    ) => {
        currentView.value = fcView;
        viewTitle.value = title;

        if (typeof window !== 'undefined') {
            localStorage.setItem(STORAGE_KEY, toUrlView(fcView));
        }

        if (!focusPending()) {
            loadRange(fcView, from, to);
        }
    };

    return {
        initialView,
        initialDate,
        currentView,
        viewTitle,
        prev,
        next,
        today,
        setView,
        focusOn,
        onRangeChange,
    };
}
