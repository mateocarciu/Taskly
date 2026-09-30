<script setup lang="ts">
import { reactive, ref, toRef, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import FullCalendar, {
    type CalendarApi as FullCalendarApi,
} from '@fullcalendar/vue3';
import type { BreadcrumbItem, CalendarEventIndex, TeamMember } from '@/types';
import { ChevronLeft, ChevronRight, Plus } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import EventDialog from '@/components/calendar/EventDialog.vue';
import CalendarPageSkeleton from '@/components/calendar/CalendarPageSkeleton.vue';
import { useCalendarConfig } from '@/composables/useCalendarConfig';
import { useCalendarView } from '@/composables/useCalendarView';
import { useEventDialog } from '@/composables/useEventDialog';
import { ensureMinimumDelay } from '@/lib/utils';

const props = defineProps<{
    events?: CalendarEventIndex[];
    teamMembers: TeamMember[];
    view?: string | null;
    start?: string | null;
    end?: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Calendar', href: '/calendar' },
];

const calendarRef = ref<{ getApi: () => FullCalendarApi } | null>(null);

const view = reactive(
    useCalendarView({
        view: props.view,
        start: props.start,
        end: props.end,
        api: () => calendarRef.value?.getApi(),
    }),
);

const localEvents = ref<CalendarEventIndex[] | null>(null);
const requestedAt = Date.now();

watch(
    () => props.events,
    async (events) => {
        if (events === undefined) return;

        await ensureMinimumDelay(requestedAt);
        localEvents.value = events;
    },
    { immediate: true },
);

const dialog = reactive(
    useEventDialog({
        events: localEvents,
        focusOn: view.focusOn,
    }),
);
const hasDragSelection = ref(false);

const openCreateAtRange = (start: Date, end: Date, allDay: boolean) => {
    hasDragSelection.value = true;
    dialog.openCreateAtRange(start, end, allDay);
};

const openEvent = (id: string) => {
    hasDragSelection.value = false;
    dialog.open(id);
};

watch(
    () => dialog.isOpen,
    (isOpen, wasOpen) => {
        if (!isOpen && wasOpen && hasDragSelection.value) {
            hasDragSelection.value = false;
            calendarRef.value?.getApi().unselect();
        }
    },
);

const { calendarOptions } = useCalendarConfig(
    toRef(() => localEvents.value || []),
    {
        initialView: view.initialView,
        initialDate: view.initialDate,
        onDateClick: dialog.openCreateAtDate,
        onRangeSelect: openCreateAtRange,
        onEventClick: openEvent,
        onRangeChange: view.onRangeChange,
    },
);
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
                <Button @click="dialog.openCreate"
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
                        @click="view.prev"
                        ><ChevronLeft class="size-4"
                    /></Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        aria-label="Next"
                        @click="view.next"
                        ><ChevronRight class="size-4"
                    /></Button>
                    <Button type="button" variant="outline" @click="view.today">
                        Today
                    </Button>
                </div>
                <h2 class="text-xl font-semibold">{{ view.viewTitle }}</h2>
                <div class="flex items-center">
                    <Button
                        type="button"
                        :variant="
                            view.currentView === 'dayGridMonth'
                                ? 'default'
                                : 'outline'
                        "
                        class="rounded-r-none"
                        @click="view.setView('dayGridMonth')"
                        >Month</Button
                    >
                    <Button
                        type="button"
                        :variant="
                            view.currentView === 'timeGridWeek'
                                ? 'default'
                                : 'outline'
                        "
                        class="-ml-px rounded-none"
                        @click="view.setView('timeGridWeek')"
                        >Week</Button
                    >
                    <Button
                        type="button"
                        :variant="
                            view.currentView === 'timeGridDay'
                                ? 'default'
                                : 'outline'
                        "
                        class="-ml-px rounded-l-none"
                        @click="view.setView('timeGridDay')"
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
            v-model:open="dialog.isOpen"
            :team-members="teamMembers"
            :default-start="dialog.defaultStart"
            :default-end="dialog.defaultEnd"
            :event="dialog.event"
            :is-loading="dialog.isLoading"
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
