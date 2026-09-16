import { computed, type Ref } from 'vue';
import { toDateInputValue } from './useDateFormatter';
import type { CalendarOptions, DatesSetInfo } from '@fullcalendar/vue3';
import formaTheme from '@fullcalendar/vue3/themes/forma';
import interactionPlugin from '@fullcalendar/vue3/interaction';
import dayGridPlugin from '@fullcalendar/vue3/daygrid';
import timeGridPlugin from '@fullcalendar/vue3/timegrid';
import type { CalendarEventIndex } from '@/types';

interface CalendarCallbacks {
    onDateClick: (date: Date, allDay: boolean) => void;
    onRangeSelect: (start: Date, end: Date, allDay: boolean) => void;
    onEventClick: (id: string) => void;
    onDateRangeChange: (
        start: string,
        end: string,
        viewType: string,
        viewTitle: string,
    ) => void;
}

/**
 * Build reactive FullCalendar options from events and UI callbacks.
 */
export function useCalendarConfig(
    events: Ref<CalendarEventIndex[]>,
    callbacks: CalendarCallbacks,
    initialView = 'dayGridMonth',
    initialDate: string | null = null,
) {
    const calendarEvents = computed(() =>
        events.value.map((event) => ({
            id: String(event.id),
            title: event.title,
            start: event.start_at,
            end: event.end_at,
            classNames: event.has_video ? ['event-video'] : ['event-standard'],
            extendedProps: { hasVideo: event.has_video },
        })),
    );

    const calendarOptions = computed<CalendarOptions>(() => {
        const options: CalendarOptions = {
            plugins: [formaTheme, interactionPlugin, dayGridPlugin, timeGridPlugin],
            initialView,
            headerToolbar: false,
            views: {
                timeGridWeek: {
                    type: 'timeGrid',
                    duration: { weeks: 1 },
                    allDaySlot: false,
                    titleFormat: { year: 'numeric', month: 'short', day: 'numeric' },
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
            dayMaxEvents: true,
            height: '100%',
            expandRows: true,
            navLinks: true,
            navLinkDayClick: 'timeGridDay',
            selectable: true,
            events: calendarEvents.value,
            datesSet: (info: DatesSetInfo) => {
                callbacks.onDateRangeChange(
                    toDateInputValue(info.start),
                    toDateInputValue(info.end),
                    info.view.type,
                    info.view.title,
                );
            },
            dateClick: (info: { date: Date }) => {
                const start = new Date(info.date);
                const allDay = start.getHours() === 0 && start.getMinutes() === 0;
                callbacks.onDateClick(start, allDay);
            },
            select: (info: { start: Date; end: Date; allDay: boolean }) => {
                callbacks.onRangeSelect(info.start, info.end, info.allDay);
            },
            eventClick: (info: { event: { id?: string } }) => {
                if (info.event.id) callbacks.onEventClick(info.event.id);
            },
        };

        if (initialDate) {
            options.initialDate = initialDate;
        }

        return options;
    });

    return { calendarOptions };
}
