<script setup lang="ts">
import { computed, ref, toRef, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import FullCalendar, {
    type CalendarApi as FullCalendarApi,
} from '@fullcalendar/vue3';
import type {
    CalendarEvent,
    CalendarEventIndex,
    BreadcrumbItem,
    TeamMember,
} from '@/types';
import { ChevronLeft, ChevronRight, Plus } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import EventDialog from '@/components/calendar/EventDialog.vue';
import CalendarPageSkeleton from '@/components/calendar/CalendarPageSkeleton.vue';
import { useCalendarConfig } from '@/composables/useCalendarConfig';
import { ensureMinimumDelay } from '@/lib/utils';

const props = defineProps<{
    events?: CalendarEventIndex[];
    teamMembers: TeamMember[];
    view?: string | null;
    start?: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Calendar', href: '/calendar' },
];

const VIEW_ALIASES: Record<string, string> = {
    month: 'dayGridMonth',
    week: 'timeGridWeek',
    day: 'timeGridDay',
};

const VIEW_ALIASES_REVERSE: Record<string, string> = Object.fromEntries(
    Object.entries(VIEW_ALIASES).map(([alias, fc]) => [fc, alias]),
);

const toFcView = (alias: string): string => VIEW_ALIASES[alias] ?? alias;
const toUrlView = (fc: string): string => VIEW_ALIASES_REVERSE[fc] ?? fc;

const STORAGE_KEY = 'taskly_calendar_view';

//  Initial State — priority: backend prop > localStorage > default
const storageAlias =
    typeof window !== 'undefined' ? localStorage.getItem(STORAGE_KEY) : null;

const resolvedAlias = props.view || storageAlias || 'month';
const initialView = toFcView(resolvedAlias);
const initialDate = props.start || null;

const page = usePage();
const eventIdFromUrl = computed(() => {
    if (typeof window === 'undefined') return null;
    const url = new URL(page.url, window.location.origin);
    const val = url.searchParams.get('id');
    return val ? parseInt(val, 10) : null;
});

const isDialogOpen = ref(false);
const activeEvent = ref<CalendarEvent | null>(null);
const selectedStart = ref<string | null>(null);
const selectedEnd = ref<string | null>(null);

const calendarRef = ref<{ getApi: () => FullCalendarApi } | null>(null);
const currentView = ref(initialView);
const viewTitle = ref('');

const getApi = () => calendarRef.value?.getApi();

const localEvents = ref<CalendarEventIndex[] | null>(null);
const startTime = Date.now();

const goPrev = () => getApi()?.prev();
const goNext = () => getApi()?.next();
const goToday = () => getApi()?.today();
const setView = (view: string) => getApi()?.changeView(view);

const openCreate = () => {
    activeEvent.value = null;
    selectedStart.value = null;
    selectedEnd.value = null;
    isDialogOpen.value = true;
};

const openCreateFromDate = (date: Date, allDay: boolean) => {
    const start = new Date(date);
    if (allDay) start.setHours(9, 0, 0, 0);

    activeEvent.value = null;
    selectedStart.value = start.toISOString();
    selectedEnd.value = new Date(
        start.getTime() + 60 * 60 * 1000,
    ).toISOString();
    isDialogOpen.value = true;
};

const openCreateFromRange = (start: Date, end: Date, allDay: boolean) => {
    const rangeStart = new Date(start);
    let rangeEnd = new Date(end);

    if (allDay) {
        rangeStart.setHours(9, 0, 0, 0);
        const lastDay = new Date(rangeEnd);
        lastDay.setDate(lastDay.getDate() - 1);
        if (lastDay < rangeStart) lastDay.setTime(rangeStart.getTime());
        lastDay.setHours(10, 0, 0, 0);
        rangeEnd = lastDay;
    }

    activeEvent.value = null;
    selectedStart.value = rangeStart.toISOString();
    selectedEnd.value = rangeEnd.toISOString();
    isDialogOpen.value = true;
};

const isLoadingEvent = ref(false);

const openEvent = async (id: string | number) => {
    const partialEvent = (props.events || []).find(
        (e) => String(e.id) === String(id),
    );
    if (partialEvent) {
        activeEvent.value = partialEvent as unknown as CalendarEvent;
        isDialogOpen.value = true;
    } else {
        activeEvent.value = { id: Number(id) } as CalendarEvent;
    }

    const startedAt = Date.now();

    try {
        isLoadingEvent.value = true;
        const response = await fetch(`/calendar/events/${id}`);
        if (!response.ok) throw new Error('Failed to fetch event');

        activeEvent.value = await response.json();
        if (!isDialogOpen.value) {
            isDialogOpen.value = true;
        }
    } catch (error) {
        console.error(error);
        toast.error('Event not found');
        isDialogOpen.value = false;
        activeEvent.value = null;
        if (typeof window !== 'undefined') {
            const url = new URL(window.location.href);
            url.searchParams.delete('id');
            window.history.replaceState({}, '', url.toString());
        }
    } finally {
        await ensureMinimumDelay(startedAt);
        isLoadingEvent.value = false;
    }
};

const { calendarOptions } = useCalendarConfig(
    toRef(() => localEvents.value || []),
    {
        onDateClick: openCreateFromDate,
        onRangeSelect: openCreateFromRange,
        onEventClick: openEvent,
        onDateRangeChange: (start, end, viewType, title) => {
            currentView.value = viewType;
            viewTitle.value = title;

            const urlAlias = toUrlView(viewType);

            if (typeof window !== 'undefined') {
                localStorage.setItem(STORAGE_KEY, urlAlias);
            }

            const currentEvent =
                isDialogOpen.value && activeEvent.value
                    ? activeEvent.value.id
                    : undefined;

            router.get(
                '/calendar',
                {
                    view: urlAlias,
                    start,
                    end,
                    ...(currentEvent ? { id: currentEvent } : {}),
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    only: ['events'],
                    replace: true,
                },
            );
        },
    },
    initialView,
    initialDate,
);

watch(
    () => props.events,
    async (newEvents) => {
        if (newEvents !== undefined) {
            await ensureMinimumDelay(startTime);
            localEvents.value = newEvents;
        }
    },
    { immediate: true },
);

watch(
    eventIdFromUrl,
    (id) => {
        if (id) {
            if (!activeEvent.value || activeEvent.value.id !== id) {
                openEvent(id);
            }
        } else {
            isDialogOpen.value = false;
        }
    },
    { immediate: true },
);

watch(isDialogOpen, (isOpen) => {
    if (typeof window === 'undefined') return;
    const url = new URL(window.location.href);
    if (isOpen && activeEvent.value) {
        url.searchParams.set('id', activeEvent.value.id.toString());
    } else {
        url.searchParams.delete('id');
        if (!isOpen) activeEvent.value = null;
    }
    window.history.replaceState({}, '', url.toString());
});
</script>

<template>
    <Head title="Calendar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100svh-4rem)] flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Calendar</h1>
                    <p class="text-sm text-muted-foreground">
                        Plan events, meetings and the work that moves them
                        forward.
                    </p>
                </div>
                <Button @click="openCreate"
                    ><Plus class="mr-2 size-4" />Create event</Button
                >
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        aria-label="Previous"
                        @click="goPrev"
                        ><ChevronLeft class="size-4"
                    /></Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        aria-label="Next"
                        @click="goNext"
                        ><ChevronRight class="size-4"
                    /></Button>
                    <Button type="button" variant="outline" @click="goToday">
                        Today
                    </Button>
                </div>
                <h2 class="text-xl font-semibold">{{ viewTitle }}</h2>
                <div class="flex items-center">
                    <Button
                        type="button"
                        :variant="
                            currentView === 'dayGridMonth'
                                ? 'default'
                                : 'outline'
                        "
                        class="rounded-r-none"
                        @click="setView('dayGridMonth')"
                        >Month</Button
                    >
                    <Button
                        type="button"
                        :variant="
                            currentView === 'timeGridWeek'
                                ? 'default'
                                : 'outline'
                        "
                        class="-ml-px rounded-none"
                        @click="setView('timeGridWeek')"
                        >Week</Button
                    >
                    <Button
                        type="button"
                        :variant="
                            currentView === 'timeGridDay'
                                ? 'default'
                                : 'outline'
                        "
                        class="-ml-px rounded-l-none"
                        @click="setView('timeGridDay')"
                        >Day</Button
                    >
                </div>
            </div>

            <CalendarPageSkeleton v-if="!localEvents" />
            <div v-else class="min-h-0 flex-1 overflow-hidden">
                <FullCalendar
                    ref="calendarRef"
                    :options="calendarOptions"
                    class="taskly-full-calendar h-full min-h-0 w-full"
                />
            </div>
        </div>
        <EventDialog
            v-model:open="isDialogOpen"
            :team-members="teamMembers"
            :default-start="selectedStart"
            :default-end="selectedEnd"
            :event="activeEvent"
            :is-loading="isLoadingEvent"
        />
    </AppLayout>
</template>

<style>
body {
    --fc-forma-primary: var(--primary);
    --fc-forma-primary-over: color-mix(
        in srgb,
        var(--primary) 90%,
        transparent
    );
    --fc-forma-primary-down: color-mix(
        in srgb,
        var(--primary) 80%,
        transparent
    );
    --fc-forma-primary-foreground: var(--primary-foreground);

    --fc-forma-event: var(--primary);
    --fc-forma-event-contrast: var(--primary-foreground);
    --fc-forma-background-event: var(--primary);
    --fc-forma-highlight: color-mix(in srgb, var(--primary) 8%, transparent);
    --fc-forma-ring-color: var(--ring);
}

.taskly-full-calendar {
    font-family: var(--font-sans, ui-sans-serif, system-ui, sans-serif);
    font-size: 0.875rem;
}

.taskly-full-calendar .taskly-event,
.taskly-full-calendar .taskly-event * {
    cursor: pointer;
}

.taskly-full-calendar .event-video {
    --fc-forma-event: var(--chart-2);
    --fc-forma-event-contrast: #fff;
}
</style>
