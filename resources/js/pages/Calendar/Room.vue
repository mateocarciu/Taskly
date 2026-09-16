<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import type { CalendarEvent } from '@/types';
import { ChevronLeft, Loader2, Video } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useExternalScript } from '@/composables/useExternalScript';
import { formatShortDate, formatShortTime } from '@/composables/useDateFormatter';

declare global {
    interface Window {
        JitsiMeetExternalAPI: any;
    }
}

const props = defineProps<{
    event: CalendarEvent;
    jitsiDomain: string;
    jwt?: string | null;
}>();

const formattedDate = formatShortDate(props.event.start_at);
const formattedStart = formatShortTime(props.event.start_at);
const formattedEnd = formatShortTime(props.event.end_at);

const container = ref<HTMLElement | null>(null);
let api: any = null;

const { isLoading } = useExternalScript(
    `https://${props.jitsiDomain}/external_api.js`,
    () => window.JitsiMeetExternalAPI,
);

const initJitsi = () => {
    if (api) return;

    const options: any = {
        roomName: props.event.room_name,
        width: '100%',
        height: '100%',
        parentNode: container.value,
        configOverwrite: {
            startWithAudioMuted: true,
            startWithVideoMuted: true,
        },
    };

    if (props.jwt) {
        options.jwt = props.jwt;
    }

    api = new window.JitsiMeetExternalAPI(props.jitsiDomain, options);
};

watch(isLoading, (loading) => {
    if (loading || !window.JitsiMeetExternalAPI || !container.value) return;
    initJitsi();
});

onMounted(() => {
    if (!isLoading.value && window.JitsiMeetExternalAPI && container.value) {
        initJitsi();
    }
});

onUnmounted(() => api?.dispose());
</script>

<template>
    <Head :title="event.title" />
    <div class="flex h-screen w-full flex-col bg-background">
        <header class="flex h-16 items-center gap-4 border-b px-4 sm:px-6">
            <Button variant="ghost" size="icon" class="shrink-0" as-child
                ><Link href="/calendar"><ChevronLeft class="size-4" /></Link
            ></Button>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="relative flex size-2 shrink-0">
                        <span
                            class="absolute inline-flex size-full animate-ping rounded-full bg-rose-400 opacity-75"
                        />
                        <span
                            class="relative inline-flex size-2 rounded-full bg-rose-500"
                        />
                    </span>
                    <h1 class="truncate text-base font-semibold sm:text-lg">
                        {{ event.title }}
                    </h1>
                </div>
                <p class="truncate text-xs text-muted-foreground">
                    {{ formattedDate }} · {{ formattedStart }} –
                    {{ formattedEnd
                    }}{{
                        event.organizer?.name
                            ? ` · Hosted by ${event.organizer.name}`
                            : ''
                    }}
                </p>
            </div>
            <div
                class="hidden shrink-0 items-center gap-2 text-xs text-muted-foreground sm:flex"
            >
                <Video class="size-4" />
                <span>Live video room</span>
            </div>
        </header>
        <div class="relative flex-1 bg-black">
            <div
                v-if="isLoading"
                class="absolute inset-0 flex items-center justify-center"
            >
                <Loader2 class="size-8 animate-spin text-primary" />
            </div>
            <div ref="container" class="h-full w-full" />
        </div>
    </div>
</template>
