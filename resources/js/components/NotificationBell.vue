<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    Bell,
    BellOff,
    CalendarDays,
    Check,
    CheckCheck,
    ChevronDown,
    MessageSquare,
    UserPlus,
    X,
} from '@lucide/vue';
import { respond } from '@/routes/calendar/events';
import { index as calendarIndex } from '@/routes/calendar';
import { index, markAsRead, readAll } from '@/routes/notifications';
import { index as tasksIndex } from '@/routes/tasks';
import {
    relativeTime,
    formatDateTimeRange,
    formatDate,
    nowIso,
} from '@/composables/useDateFormatter';
import { usePaginatedList } from '@/composables/usePaginatedList';
import { useInitials } from '@/composables/useInitials';
import type { AppNotification, NotificationType } from '@/types';

const isType = (notification: AppNotification, type: NotificationType) =>
    notification.type === type;

const { getInitials } = useInitials();

const page = usePage();
const unreadCount = computed(() => page.props.notifications.unreadCount);
const {
    items: notifications,
    pagination,
    isLoadingMore,
    loadFirstPage,
    loadMore,
} = usePaginatedList<AppNotification>(
    () => ({
        items: [],
        pagination: undefined,
    }),
    {
        urlForPage: (page) => index({ query: { page } }).url,
        errorMessage: 'Failed to load more notifications',
        preserveLoadedItems: true,
    },
);
const notificationsLoaded = ref(false);

const handleOpenChange = async (open: boolean) => {
    if (!open || notificationsLoaded.value) return;

    notificationsLoaded.value = await loadFirstPage();
};

const response = (notification: AppNotification) =>
    notification.data.response && notification.data.response !== 'needs_action'
        ? notification.data.response
        : 'needs_action';

const eventDateTime = (notification: AppNotification): string | null =>
    formatDateTimeRange(
        notification.data.start_time ?? null,
        notification.data.end_time ?? null,
    );

const isTaskNotification = (notification: AppNotification) =>
    isType(notification, 'task_assigned') ||
    isType(notification, 'task_commented');

const iconFor = (notification: AppNotification) => {
    if (isType(notification, 'task_assigned')) return UserPlus;
    if (isType(notification, 'task_commented')) return MessageSquare;
    return CalendarDays;
};

const actorNameFor = (notification: AppNotification): string | null => {
    if (isType(notification, 'task_assigned')) {
        return notification.data.assigner ?? null;
    }
    if (isType(notification, 'task_commented')) {
        return notification.data.commenter ?? null;
    }
    if (isType(notification, 'event_invitation')) {
        return notification.data.inviter ?? null;
    }
    return null;
};

const initialsFor = (notification: AppNotification): string =>
    getInitials(actorNameFor(notification) ?? '');

const dueDate = (notification: AppNotification): string | null =>
    notification.data.due_date ? formatDate(notification.data.due_date) : null;

const markAllAsRead = () =>
    router.post(readAll().url, {}, { preserveScroll: true });

const openTask = async (notification: AppNotification) => {
    const taskId = notification.data.task_id;
    if (typeof taskId !== 'number') return;

    notificationsLoaded.value = false;

    if (!notification.read_at) {
        notification.read_at = nowIso();

        try {
            await fetch(markAsRead({ notification: notification.id }).url, {
                method: 'POST',
                headers: { Accept: 'application/json' },
            });
            if (page.props.notifications.unreadCount > 0) {
                page.props.notifications.unreadCount--;
            }
        } catch {}
    }

    const currentPath = window.location.pathname;
    if (currentPath === '/tasks' || currentPath.startsWith('/tasks?')) {
        window.dispatchEvent(
            new CustomEvent('open-task', { detail: { taskId } }),
        );
    } else {
        router.visit(tasksIndex({ query: { id: taskId } }).url, {
            preserveScroll: false,
        });
    }
};

const openEvent = async (notification: AppNotification) => {
    const eventId = notification.data.event_id;
    if (typeof eventId !== 'number') return;

    notificationsLoaded.value = false;

    if (!notification.read_at) {
        notification.read_at = nowIso();

        try {
            await fetch(markAsRead({ notification: notification.id }).url, {
                method: 'POST',
                headers: { Accept: 'application/json' },
            });
            if (page.props.notifications.unreadCount > 0) {
                page.props.notifications.unreadCount--;
            }
        } catch {}
    }

    const currentPath = window.location.pathname;
    if (currentPath === '/calendar' || currentPath.startsWith('/calendar?')) {
        window.dispatchEvent(
            new CustomEvent('open-event', { detail: { eventId } }),
        );
    } else {
        router.visit(calendarIndex({ query: { id: eventId } }).url, {
            preserveScroll: false,
        });
    }
};

const respondTo = (
    notification: AppNotification,
    response: 'accepted' | 'declined',
) => {
    const wasUnread = !notification.read_at;
    router.post(
        respond({ event: notification.data.event_id!, response }).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                notification.data.response = response;
                notification.read_at = nowIso();
                if (wasUnread && page.props.notifications.unreadCount > 0) {
                    page.props.notifications.unreadCount--;
                }
            },
        },
    );
};
</script>

<template>
    <DropdownMenu @update:open="handleOpenChange">
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
                <div
                    v-if="isLoadingMore && notifications.length === 0"
                    class="flex items-center justify-center gap-2 px-4 py-12 text-sm text-muted-foreground"
                >
                    <Spinner class="size-4" />
                    Loading notifications...
                </div>

                <template v-else-if="notifications.length > 0">
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="flex flex-col gap-2 px-4 py-3 transition-colors hover:bg-muted/50"
                        :class="[
                            !notification.read_at ? 'bg-primary/2' : '',
                            isTaskNotification(notification) ||
                            isType(notification, 'event_invitation')
                                ? 'cursor-pointer'
                                : '',
                        ]"
                        :role="
                            isTaskNotification(notification) ||
                            isType(notification, 'event_invitation')
                                ? 'button'
                                : undefined
                        "
                        :tabindex="
                            isTaskNotification(notification) ||
                            isType(notification, 'event_invitation')
                                ? 0
                                : undefined
                        "
                        @click="
                            isTaskNotification(notification)
                                ? openTask(notification)
                                : isType(notification, 'event_invitation')
                                  ? openEvent(notification)
                                  : undefined
                        "
                        @keydown.enter="
                            isTaskNotification(notification)
                                ? openTask(notification)
                                : isType(notification, 'event_invitation')
                                  ? openEvent(notification)
                                  : undefined
                        "
                    >
                        <div class="flex items-start gap-3">
                            <div class="relative mt-0.5 shrink-0">
                                <Avatar class="size-9">
                                    <AvatarFallback
                                        class="text-xs font-medium text-muted-foreground uppercase"
                                    >
                                        <template
                                            v-if="initialsFor(notification)"
                                        >
                                            {{ initialsFor(notification) }}
                                        </template>
                                        <component
                                            v-else
                                            :is="iconFor(notification)"
                                            class="size-4"
                                        />
                                    </AvatarFallback>
                                </Avatar>
                                <span
                                    v-if="initialsFor(notification)"
                                    class="absolute -right-1 -bottom-1 flex size-4 items-center justify-center rounded-full border border-border bg-background text-muted-foreground"
                                >
                                    <component
                                        :is="iconFor(notification)"
                                        class="size-2.5"
                                        :stroke-width="2.5"
                                    />
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    v-if="
                                        isType(notification, 'event_invitation')
                                    "
                                    class="text-sm leading-snug text-foreground"
                                >
                                    <span class="font-medium">{{
                                        notification.data.inviter
                                    }}</span>
                                    invited you to
                                    <span class="font-medium">{{
                                        notification.data.title
                                    }}</span>
                                </p>
                                <p
                                    v-else-if="
                                        isType(notification, 'task_assigned')
                                    "
                                    class="text-sm leading-snug text-foreground"
                                >
                                    <span class="font-medium">{{
                                        notification.data.assigner
                                    }}</span>
                                    assigned you to
                                    <span class="font-medium">{{
                                        notification.data.task_title
                                    }}</span>
                                </p>
                                <p
                                    v-else-if="
                                        isType(notification, 'task_commented')
                                    "
                                    class="text-sm leading-snug text-foreground"
                                >
                                    <span class="font-medium">{{
                                        notification.data.commenter
                                    }}</span>
                                    commented on
                                    <span class="font-medium">{{
                                        notification.data.task_title
                                    }}</span>
                                </p>
                                <p
                                    v-else
                                    class="text-sm leading-snug text-foreground"
                                >
                                    {{
                                        notification.data.title ??
                                        'You have a new notification'
                                    }}
                                </p>

                                <p
                                    v-if="
                                        isType(
                                            notification,
                                            'event_invitation',
                                        ) && eventDateTime(notification)
                                    "
                                    class="mt-0.5 text-xs text-muted-foreground"
                                >
                                    {{ eventDateTime(notification) }}
                                </p>
                                <p
                                    v-else-if="
                                        isType(notification, 'task_assigned') &&
                                        dueDate(notification)
                                    "
                                    class="mt-0.5 text-xs text-muted-foreground"
                                >
                                    Due {{ dueDate(notification) }}
                                </p>
                                <p
                                    v-else-if="
                                        isType(
                                            notification,
                                            'task_commented',
                                        ) && notification.data.comment
                                    "
                                    class="mt-0.5 line-clamp-2 text-xs text-muted-foreground"
                                >
                                    "{{ notification.data.comment }}"
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
                            class="flex items-center gap-2 pl-12"
                        >
                            <template
                                v-if="response(notification) === 'needs_action'"
                            >
                                <Button
                                    size="sm"
                                    class="h-8"
                                    @click.stop="
                                        respondTo(notification, 'accepted')
                                    "
                                >
                                    <Check class="mr-1.5 size-3.5" />
                                    Accept
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    class="h-8 text-muted-foreground hover:text-foreground"
                                    @click.stop="
                                        respondTo(notification, 'declined')
                                    "
                                >
                                    <X class="mr-1.5 size-3.5" />
                                    Decline
                                </Button>
                            </template>
                            <div v-else class="flex items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs font-medium"
                                    :class="
                                        response(notification) === 'accepted'
                                            ? 'text-primary'
                                            : 'text-muted-foreground'
                                    "
                                >
                                    <Check
                                        v-if="
                                            response(notification) ===
                                            'accepted'
                                        "
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
                                <button
                                    type="button"
                                    class="text-xs text-muted-foreground underline hover:text-foreground"
                                    @click.stop="
                                        respondTo(
                                            notification,
                                            response(notification) ===
                                                'accepted'
                                                ? 'declined'
                                                : 'accepted',
                                        )
                                    "
                                >
                                    {{
                                        response(notification) === 'accepted'
                                            ? 'Decline'
                                            : 'Accept'
                                    }}
                                </button>
                            </div>
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
                        Notifications for invitations, assignments and comments
                        will appear here.
                    </p>
                </div>

                <div v-if="pagination?.has_more" class="border-t p-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        class="h-8 w-full text-xs text-muted-foreground"
                        :disabled="isLoadingMore"
                        @click="loadMore"
                    >
                        <template v-if="isLoadingMore">
                            <Spinner class="mr-1.5 size-3.5" />
                            Loading...
                        </template>
                        <template v-else>
                            Load more notifications
                            <ChevronDown class="ml-1.5 size-3" />
                        </template>
                    </Button>
                </div>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
