<script setup lang="ts">
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Bell, BellOff, CalendarDays, Check, CheckCheck, X } from '@lucide/vue';
import { respond } from '@/routes/calendar/events';
import { readAll } from '@/routes/notifications';
import {
    relativeTime,
    formatDateTimeRange,
} from '@/composables/useDateFormatter';
import type { AppNotification, NotificationType } from '@/types';

const isType = (notification: AppNotification, type: NotificationType) =>
    notification.type === type;

const notifications = computed(
    () => usePage().props.notifications.notifications,
);
const unreadCount = computed(() => usePage().props.notifications.unreadCount);

const response = (notification: AppNotification) =>
    notification.data.response && notification.data.response !== 'needs_action'
        ? notification.data.response
        : 'needs_action';

const eventDateTime = (notification: AppNotification): string | null =>
    formatDateTimeRange(
        notification.data.start_time ?? null,
        notification.data.end_time ?? null,
    );

const markAllAsRead = () =>
    router.post(readAll().url, {}, { preserveScroll: true });

const respondTo = (eventId: number, response: 'accepted' | 'declined') =>
    router.post(
        respond({ event: eventId, response }).url,
        {},
        { preserveScroll: true },
    );
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative rounded-full"
                aria-label="Notifications"
            >
                <Bell class="size-5" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute top-1 right-1 flex min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-4 font-semibold text-destructive-foreground"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            align="end"
            :side-offset="8"
            class="w-[22rem] overflow-hidden p-0 sm:w-96"
        >
            <div class="flex items-center justify-between border-b px-4 py-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold">Notifications</span>
                    <span
                        v-if="unreadCount > 0"
                        class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                        >{{ unreadCount }} new</span
                    >
                </div>
                <button
                    v-if="unreadCount > 0"
                    type="button"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
                    @click="markAllAsRead"
                >
                    <CheckCheck class="size-3.5" />
                    Mark all as read
                </button>
            </div>

            <div class="max-h-[28rem] overflow-y-auto">
                <template v-if="notifications.length > 0">
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="flex flex-col gap-2 px-4 py-3 transition-colors hover:bg-muted/50"
                        :class="!notification.read_at ? 'bg-primary/2' : ''"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <CalendarDays class="size-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm leading-snug text-foreground">
                                    <span class="font-medium">{{
                                        notification.data.inviter
                                    }}</span>
                                    invited you to
                                    <span class="font-medium">{{
                                        notification.data.title
                                    }}</span>
                                </p>
                                <p
                                    v-if="eventDateTime(notification)"
                                    class="mt-0.5 text-xs text-muted-foreground"
                                >
                                    {{ eventDateTime(notification) }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 text-xs text-muted-foreground"
                                >{{
                                    relativeTime(notification.created_at)
                                }}</span
                            >
                        </div>
                        <div
                            v-if="isType(notification, 'event_invitation')"
                            class="flex items-center gap-2 pl-11"
                        >
                            <template
                                v-if="response(notification) === 'needs_action'"
                            >
                                <Button
                                    size="sm"
                                    class="h-8"
                                    @click="
                                        respondTo(
                                            notification.data.event_id!,
                                            'accepted',
                                        )
                                    "
                                >
                                    <Check class="mr-1.5 size-3.5" />
                                    Accept
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    class="h-8 text-muted-foreground hover:text-foreground"
                                    @click="
                                        respondTo(
                                            notification.data.event_id!,
                                            'declined',
                                        )
                                    "
                                >
                                    <X class="mr-1.5 size-3.5" />
                                    Decline
                                </Button>
                            </template>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 text-xs font-medium"
                                :class="
                                    response(notification) === 'accepted'
                                        ? 'text-primary'
                                        : 'text-muted-foreground'
                                "
                            >
                                <Check
                                    v-if="response(notification) === 'accepted'"
                                    class="size-3.5"
                                />
                                <X v-else class="size-3.5" />
                                You
                                {{
                                    response(notification) === 'accepted'
                                        ? 'accepted'
                                        : 'declined'
                                }}
                            </span>
                        </div>
                    </div>
                </template>

                <div
                    v-else
                    class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center"
                >
                    <div
                        class="flex size-11 items-center justify-center rounded-full bg-muted"
                    >
                        <BellOff class="size-5 text-muted-foreground" />
                    </div>
                    <p class="text-sm font-medium">You're all caught up</p>
                    <p class="text-xs text-muted-foreground">
                        Notifications for invitations and updates will appear
                        here.
                    </p>
                </div>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
