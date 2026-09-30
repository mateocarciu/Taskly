import { ensureMinimumDelay } from '@/lib/utils';
import type { CalendarEvent, CalendarEventIndex } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch, type Ref } from 'vue';
import { toast } from 'vue-sonner';

interface EventDialogOptions {
    events: Ref<CalendarEventIndex[] | null>;
    focusOn: (startAt: string) => void;
}

/**
 * Own the event dialog: what it shows, and the `id` query parameter that keeps
 * the open event shareable through the URL.
 */
export function useEventDialog({ events, focusOn }: EventDialogOptions) {
    const page = usePage();

    const isOpen = ref(false);
    const event = ref<CalendarEvent | null>(null);
    const isLoading = ref(false);
    const defaultStart = ref<string | null>(null);
    const defaultEnd = ref<string | null>(null);

    const urlEventId = computed(() => {
        if (typeof window === 'undefined') return null;

        const url = new URL(page.url, window.location.origin);
        const id = parseInt(url.searchParams.get('id') ?? '', 10);
        return Number.isNaN(id) ? null : id;
    });

    const writeIdToUrl = (id: number | null) => {
        if (typeof window === 'undefined') return;

        const url = new URL(window.location.href);
        if (id === null) {
            url.searchParams.delete('id');
        } else {
            url.searchParams.set('id', id.toString());
        }

        window.history.replaceState({}, '', url.toString());
    };

    const reset = () => {
        event.value = null;
        defaultStart.value = null;
        defaultEnd.value = null;
        writeIdToUrl(null);
    };

    const openCreate = () => {
        reset();
        isOpen.value = true;
    };

    const openCreateAtDate = (date: Date, allDay: boolean) => {
        const start = new Date(date);
        if (allDay) start.setHours(9, 0, 0, 0);

        openCreate();
        defaultStart.value = start.toISOString();
        defaultEnd.value = new Date(
            start.getTime() + 60 * 60 * 1000,
        ).toISOString();
    };

    const openCreateAtRange = (start: Date, end: Date) => {
        const from = new Date(start);
        const to = new Date(end);

        openCreate();
        defaultStart.value = from.toISOString();
        defaultEnd.value = to.toISOString();
    };

    const close = () => {
        reset();
        isOpen.value = false;
    };

    const open = async (id: string | number) => {
        const partial = (events.value ?? []).find(
            (e) => String(e.id) === String(id),
        );

        if (partial) {
            event.value = partial as unknown as CalendarEvent;
            isOpen.value = true;
            focusOn(partial.start_at);
        } else {
            event.value = { id: Number(id) } as CalendarEvent;
        }

        const startedAt = Date.now();

        try {
            isLoading.value = true;

            const response = await fetch(`/calendar/events/${id}`);
            if (!response.ok) throw new Error('Failed to fetch event');

            const loaded: CalendarEvent = await response.json();
            event.value = loaded;
            focusOn(loaded.start_at);
            isOpen.value = true;
        } catch (error) {
            console.error(error);
            toast.error('Event not found');
            close();
        } finally {
            await ensureMinimumDelay(startedAt);
            isLoading.value = false;
        }
    };

    watch(
        urlEventId,
        (id) => {
            if (id === null) {
                close();
            } else if (event.value?.id !== id) {
                open(id);
            }
        },
        { immediate: true },
    );

    watch(
        () => (isOpen.value && event.value ? event.value.id : null),
        (id) => {
            writeIdToUrl(id);

            if (id === null) {
                event.value = null;
            }
        },
    );

    return {
        isOpen,
        event,
        isLoading,
        defaultStart,
        defaultEnd,
        openCreate,
        openCreateAtDate,
        openCreateAtRange,
        open,
        close,
    };
}
