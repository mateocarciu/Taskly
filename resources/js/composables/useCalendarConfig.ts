import type { CalendarEventIndex } from '@/types';
import type { CalendarOptions, DatesSetInfo } from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/vue3/daygrid';
import interactionPlugin from '@fullcalendar/vue3/interaction';
import formaTheme from '@fullcalendar/vue3/themes/forma';
import timeGridPlugin from '@fullcalendar/vue3/timegrid';
import { computed, type Ref } from 'vue';
import {
    scrollTimeBeforeNow,
    toDateInputValue,
    toTimeInputValue,
} from './useDateFormatter';

export interface CalendarHandlers {
    initialView: string;
    initialDate: string | null;
    onDateClick: (date: Date, allDay: boolean) => void;
    onRangeSelect: (start: Date, end: Date, allDay: boolean) => void;
    onEventClick: (id: string) => void;
    onRangeChange: (
        start: string,
        end: string,
        viewType: string,
        title: string,
    ) => void;
}

/**
 * Build reactive FullCalendar options from events and UI callbacks.
 */
export function useCalendarConfig(
    events: Ref<CalendarEventIndex[]>,
    handlers: CalendarHandlers,
) {
    const calendarEvents = computed(() =>
        events.value.map((event) => {
            const classes = ['taskly-event'];
            if (event.is_declined) {
                classes.push('event-declined');
            } else if (event.has_video) {
                classes.push('event-video');
            } else {
                classes.push('event-standard');
            }

            return {
                id: String(event.id),
                title: event.title,
                start: event.start_at,
                end: event.end_at,
                class: classes.join(' '),
                extendedProps: {
                    hasVideo: event.has_video,
                    isDeclined: Boolean(event.is_declined),
                },
            };
        }),
    );

    const scrollTime = scrollTimeBeforeNow();

    const calendarOptions = computed<CalendarOptions>(() => ({
        plugins: [formaTheme, interactionPlugin, dayGridPlugin, timeGridPlugin],
        initialView: handlers.initialView,
        initialDate: handlers.initialDate ?? undefined,
        headerToolbar: false,
        views: {
            timeGridWeek: {
                type: 'timeGrid',
                duration: { weeks: 1 },
                allDaySlot: false,
                titleFormat: {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                },
            },
            timeGridDay: {
                type: 'timeGrid',
                duration: { days: 1 },
                allDaySlot: false,
                titleFormat: {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                },
            },
        },
        locale: 'en-gb',
        firstDay: 1,
        nowIndicator: true,
        scrollTime,
        snapDuration: '00:15:00',
        selectable: true,
        unselectAuto: false,
        selectMirror: true,
        eventContent: (arg) => {
            const content = document.createElement('div');
            const title = document.createElement('div');
            const time = document.createElement('div');

            title.textContent = arg.isMirror
                ? 'New event'
                : arg.event.title || 'Untitled event';

            if (arg.event.extendedProps.isDeclined) {
                content.classList.add('taskly-event-declined');
                title.classList.add('taskly-event-declined-title');
            }

            if (arg.view.type === 'dayGridMonth') {
                content.append(title);

                return { domNodes: [content] };
            }

            time.textContent = arg.isMirror
                ? `From ${toTimeInputValue(arg.event.start)} to ${toTimeInputValue(arg.event.end)}`
                : arg.timeText;
            content.append(title, time);

            return { domNodes: [content] };
        },
        dayMaxEvents: true,
        height: '100%',
        expandRows: true,
        navLinks: true,
        navLinkDayClick: 'timeGridDay',
        events: calendarEvents.value,
        datesSet: (info: DatesSetInfo) =>
            handlers.onRangeChange(
                toDateInputValue(info.start),
                toDateInputValue(info.end),
                info.view.type,
                info.view.title,
            ),
        dateClick: (info: { date: Date }) => {
            const start = new Date(info.date);
            const allDay = start.getHours() === 0 && start.getMinutes() === 0;
            handlers.onDateClick(start, allDay);
        },
        select: (info: { start: Date; end: Date; allDay: boolean }) =>
            handlers.onRangeSelect(info.start, info.end, info.allDay),
        eventClick: (info: { event: { id?: string } }) => {
            if (info.event.id) handlers.onEventClick(info.event.id);
        },
    }));

    return { calendarOptions };
}
