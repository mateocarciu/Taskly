<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import DateTimeField from '@/components/calendar/DateTimeField.vue';
import {
    store,
    update,
    destroy,
    room,
    respond,
} from '@/routes/calendar/events';
import { useInitials } from '@/composables/useInitials';
import type { CalendarEvent, TeamMember } from '@/types';
import {
    Save,
    Check,
    Trash,
    UserPlus,
    Video,
    X,
    Clock,
    Users,
} from '@lucide/vue';

const props = defineProps<{
    teamMembers: TeamMember[];
    defaultStart?: string | null;
    defaultEnd?: string | null;
    event?: CalendarEvent | null;
    isLoading?: boolean;
}>();

const isOpen = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    (e: 'responded', response: 'accepted' | 'declined'): void;
}>();

const currentUserId = usePage().props.auth.user.id;

const form = useForm({
    title: '',
    description: '',
    start_at: '',
    end_at: '',
    has_video: false,
    attendee_ids: [] as number[],
});

const isEdit = computed(() => Boolean(props.event));
const isOwner = computed(() => {
    const orgId = props.event?.organizer?.id ?? props.event?.organizer_id;
    return orgId === currentUserId;
});
const isReadOnly = computed(() => isEdit.value && !isOwner.value);

const localParticipationStatus = ref<'accepted' | 'declined' | null>(null);

const myParticipationStatus = computed(() => {
    if (isOwner.value) return 'accepted';
    if (localParticipationStatus.value) {
        return localParticipationStatus.value;
    }
    if (props.event?.user_participation_status) {
        return props.event.user_participation_status;
    }
    const me = props.event?.attendees?.find((p) => p.id === currentUserId);
    return me?.participation_status ?? null;
});

const canRemove = computed(() => isEdit.value && isOwner.value);

const { getInitials } = useInitials();

const attendeeStatus = (attendeeId: number) => {
    if (attendeeId === currentUserId && localParticipationStatus.value) {
        return localParticipationStatus.value;
    }
    const attendee = props.event?.attendees?.find((a) => a.id === attendeeId);
    return attendee?.participation_status ?? null;
};

const attendeeStats = computed(() => {
    const attendees = props.event?.attendees ?? [];
    let accepted = 0;
    let declined = 0;
    let pending = 0;

    for (const a of attendees) {
        const status = attendeeStatus(a.id);
        if (status === 'accepted') accepted++;
        else if (status === 'declined') declined++;
        else pending++;
    }

    return { total: attendees.length, accepted, declined, pending };
});

const fillFromEvent = () => {
    if (!props.event) return;
    form.title = props.event.title;
    form.description = props.event.description ?? '';
    form.start_at = props.event.start_at;
    form.end_at = props.event.end_at;
    form.has_video = props.event.has_video;
    form.attendee_ids = (props.event.attendees ?? [])
        .filter((p) => p.id !== currentUserId)
        .map((p) => p.id);
};

const applyDefaultStart = () => {
    if (!props.defaultStart) return;
    const start = new Date(props.defaultStart);
    if (Number.isNaN(start.getTime())) return;

    form.start_at = start.toISOString();

    if (props.defaultEnd) {
        const end = new Date(props.defaultEnd);
        if (!Number.isNaN(end.getTime())) {
            form.end_at = end.toISOString();
            return;
        }
    }

    form.end_at = new Date(start.getTime() + 60 * 60 * 1000).toISOString();
};

const submit = () => {
    if (props.event) {
        form.put(update(props.event.id).url, {
            onSuccess: () => {
                toast.success('Event updated');
                isOpen.value = false;
            },
        });
        return;
    }
    form.post(store().url, {
        onSuccess: () => {
            toast.success('Event created');
            isOpen.value = false;
            form.reset();
        },
    });
};

const removeEvent = () => {
    if (!props.event) return;
    router.delete(destroy(props.event.id).url, {
        onSuccess: () => {
            toast.success('Event removed from your calendar');
            isOpen.value = false;
        },
    });
};

const joinRoom = () => {
    if (!props.event) return;
    isOpen.value = false;
    router.visit(room(props.event.id).url);
};

const isResponding = ref(false);

const respondToEvent = (response: 'accepted' | 'declined') => {
    if (!props.event) return;
    isResponding.value = true;
    router.post(
        respond({ event: props.event.id, response }).url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                localParticipationStatus.value = response;
                emit('responded', response);
                if (response === 'accepted') {
                    toast.success('Event invitation accepted');
                } else {
                    toast.success('Event invitation declined');
                }
            },
            onFinish: () => {
                isResponding.value = false;
            },
        },
    );
};

watch(
    () => [props.event?.id, isOpen.value],
    () => {
        localParticipationStatus.value = null;
    },
);

watch(isOpen, () => {
    form.clearErrors();
    if (props.event) {
        fillFromEvent();
    } else {
        form.reset();
        applyDefaultStart();
    }
});

watch(
    () => props.event,
    (newEvent) => {
        if (newEvent && isOpen.value) {
            fillFromEvent();
        }
    },
    { deep: true },
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent
            class="w-[96vw] max-w-[720px] overflow-hidden bg-background p-0 focus:outline-none sm:max-w-[720px]"
            :show-close-button="false"
            @open-auto-focus.prevent
        >
            <div
                class="max-h-[92vh] overflow-y-auto overscroll-contain bg-background focus:outline-none"
            >
                <DialogHeader
                    class="sticky top-0 z-20 border-b bg-background/95 px-6 pt-6 pr-14 pb-4 backdrop-blur supports-backdrop-filter:bg-background/80"
                >
                    <DialogTitle class="text-xl">{{
                        isEdit ? 'Event details' : 'Create an event'
                    }}</DialogTitle>
                    <DialogDescription class="mt-0.5">
                        {{
                            isEdit
                                ? 'View and edit the details of this event.'
                                : 'Put a moment on your team calendar and invite the people who need to be there.'
                        }}
                    </DialogDescription>

                    <button
                        type="button"
                        data-slot="dialog-close"
                        class="absolute top-4 right-4 rounded-xs p-0 opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-hidden disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground"
                        @click="isOpen = false"
                    >
                        <X class="size-4" />
                        <span class="sr-only">Close</span>
                    </button>
                </DialogHeader>

                <form
                    :class="{
                        'pointer-events-none opacity-60 transition-opacity duration-200':
                            isLoading,
                    }"
                    class="relative space-y-5 px-6 pt-4 pb-6"
                    @submit.prevent="submit"
                >
                    <div class="space-y-2">
                        <Label for="title">Event title</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            placeholder="e.g. Product review"
                            required
                            :disabled="isReadOnly"
                        />
                        <InputError :message="form.errors.title" />
                    </div>
                    <div class="space-y-2">
                        <Label for="description"
                            >Description
                            <span class="font-normal text-muted-foreground"
                                >(optional)</span
                            ></Label
                        >
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="What should everyone know?"
                            class="min-h-20 resize-none"
                            :disabled="isReadOnly"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                    <div class="space-y-2">
                        <Label>When</Label>
                        <div
                            class="grid grid-cols-1 gap-3 rounded-xl border p-3 sm:grid-cols-2 sm:p-4"
                        >
                            <div class="grid gap-2">
                                <DateTimeField
                                    v-model="form.start_at"
                                    label="Starts"
                                    :disabled="isReadOnly"
                                    :error="form.errors.start_at"
                                />
                            </div>
                            <div class="grid gap-2">
                                <DateTimeField
                                    v-model="form.end_at"
                                    label="Ends"
                                    :disabled="isReadOnly"
                                    :error="form.errors.end_at"
                                />
                            </div>
                        </div>
                    </div>
                    <div v-if="isReadOnly" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <Label
                                class="flex items-center gap-1.5 font-medium"
                            >
                                <Users class="size-4 text-muted-foreground" />
                                Participants
                            </Label>
                            <span
                                v-if="event?.attendees?.length"
                                class="text-xs text-muted-foreground"
                            >
                                {{ attendeeStats.accepted }} accepted ·
                                {{ attendeeStats.declined }} declined ·
                                {{ attendeeStats.pending }} pending
                            </span>
                        </div>

                        <div
                            class="max-h-52 space-y-1.5 overflow-y-auto rounded-xl border p-2"
                        >
                            <!-- Organizer -->
                            <div
                                v-if="event?.organizer"
                                class="flex items-center justify-between rounded-lg bg-muted/40 px-3 py-2 text-sm"
                            >
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <Avatar class="size-7 border border-border">
                                        <AvatarFallback
                                            class="text-[10px] font-semibold"
                                        >
                                            {{
                                                getInitials(
                                                    event.organizer.name,
                                                )
                                            }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span
                                        class="truncate font-medium text-foreground"
                                    >
                                        {{ event.organizer.name }}
                                        <span
                                            v-if="
                                                event.organizer.id ===
                                                currentUserId
                                            "
                                            class="ml-1 text-xs font-normal text-muted-foreground"
                                            >(You)</span
                                        >
                                    </span>
                                </div>
                                <Badge
                                    variant="secondary"
                                    class="text-[11px] font-normal"
                                >
                                    Organizer
                                </Badge>
                            </div>

                            <!-- Attendees -->
                            <template v-if="event?.attendees?.length">
                                <div
                                    v-for="attendee in event.attendees"
                                    :key="attendee.id"
                                    class="flex items-center justify-between rounded-lg px-3 py-2 text-sm transition-colors hover:bg-muted/30"
                                >
                                    <div
                                        class="flex min-w-0 items-center gap-2.5"
                                    >
                                        <Avatar
                                            class="size-7 border border-border"
                                        >
                                            <AvatarFallback
                                                class="text-[10px] font-semibold"
                                            >
                                                {{ getInitials(attendee.name) }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <span class="truncate text-foreground">
                                            {{ attendee.name }}
                                            <span
                                                v-if="
                                                    attendee.id ===
                                                    currentUserId
                                                "
                                                class="ml-1 text-xs text-muted-foreground"
                                                >(You)</span
                                            >
                                        </span>
                                    </div>

                                    <Badge
                                        v-if="
                                            attendeeStatus(attendee.id) ===
                                            'accepted'
                                        "
                                        variant="outline"
                                        class="border-emerald-500/30 bg-emerald-500/10 text-[11px] text-emerald-600 dark:text-emerald-400"
                                    >
                                        <Check class="mr-1 size-3" /> Accepted
                                    </Badge>
                                    <Badge
                                        v-else-if="
                                            attendeeStatus(attendee.id) ===
                                            'declined'
                                        "
                                        variant="outline"
                                        class="border-destructive/30 bg-destructive/10 text-[11px] text-destructive"
                                    >
                                        <X class="mr-1 size-3" /> Declined
                                    </Badge>
                                    <Badge
                                        v-else
                                        variant="outline"
                                        class="border-amber-500/30 bg-amber-500/10 text-[11px] text-amber-600 dark:text-amber-400"
                                    >
                                        <Clock class="mr-1 size-3" /> Pending
                                    </Badge>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div v-else-if="teamMembers.length" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label class="flex items-center gap-1.5">
                                <Users class="size-4 text-muted-foreground" />
                                Invite teammates
                            </Label>
                            <div
                                class="flex items-center gap-2 text-xs text-muted-foreground"
                            >
                                <span v-if="isEdit && event?.attendees?.length">
                                    {{ attendeeStats.accepted }} accepted ·
                                    {{ attendeeStats.declined }} declined ·
                                    {{ attendeeStats.pending }} pending
                                </span>
                                <span v-else>
                                    {{ form.attendee_ids.length }} selected
                                </span>
                            </div>
                        </div>
                        <div
                            class="grid max-h-48 gap-1 overflow-y-auto rounded-xl border p-2 sm:grid-cols-2"
                        >
                            <label
                                v-for="member in teamMembers"
                                :key="member.id"
                                class="flex cursor-pointer items-center justify-between gap-2 rounded-lg px-2.5 py-2 text-sm transition-colors hover:bg-muted"
                                :class="[
                                    form.attendee_ids.includes(member.id)
                                        ? 'bg-primary/8 text-primary'
                                        : '',
                                ]"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <span
                                        class="flex size-5 shrink-0 items-center justify-center rounded border"
                                        :class="
                                            form.attendee_ids.includes(
                                                member.id,
                                            )
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-input'
                                        "
                                    >
                                        <Check
                                            v-if="
                                                form.attendee_ids.includes(
                                                    member.id,
                                                )
                                            "
                                            class="size-3.5"
                                        />
                                    </span>
                                    <input
                                        v-model="form.attendee_ids"
                                        class="sr-only"
                                        type="checkbox"
                                        :value="member.id"
                                    />
                                    <span class="truncate">{{
                                        member.name
                                    }}</span>
                                </div>

                                <template
                                    v-if="
                                        isEdit &&
                                        form.attendee_ids.includes(member.id)
                                    "
                                >
                                    <Badge
                                        v-if="
                                            attendeeStatus(member.id) ===
                                            'accepted'
                                        "
                                        variant="outline"
                                        class="shrink-0 border-emerald-500/30 bg-emerald-500/10 text-[10px] text-emerald-600 dark:text-emerald-400"
                                    >
                                        <Check class="mr-0.5 size-2.5" />
                                        Accepted
                                    </Badge>
                                    <Badge
                                        v-else-if="
                                            attendeeStatus(member.id) ===
                                            'declined'
                                        "
                                        variant="outline"
                                        class="shrink-0 border-destructive/30 bg-destructive/10 text-[10px] text-destructive"
                                    >
                                        <X class="mr-0.5 size-2.5" /> Declined
                                    </Badge>
                                    <Badge
                                        v-else-if="
                                            attendeeStatus(member.id) ===
                                            'needs_action'
                                        "
                                        variant="outline"
                                        class="shrink-0 border-amber-500/30 bg-amber-500/10 text-[10px] text-amber-600 dark:text-amber-400"
                                    >
                                        <Clock class="mr-0.5 size-2.5" />
                                        Pending
                                    </Badge>
                                    <Badge
                                        v-else
                                        variant="secondary"
                                        class="shrink-0 text-[10px]"
                                    >
                                        New
                                    </Badge>
                                </template>
                            </label>
                        </div>
                    </div>
                    <div
                        v-if="isReadOnly && event?.has_video"
                        class="flex items-center gap-3 rounded-xl border border-chart-2/40 bg-chart-2/5 p-3.5"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-chart-2/15 text-chart-2"
                        >
                            <Video class="size-4" />
                        </span>
                        <div class="flex-1">
                            <span class="block text-sm font-medium"
                                >Video Meeting</span
                            >
                            <span class="block text-xs text-muted-foreground"
                                >This event has a Jitsi room attached</span
                            >
                        </div>
                    </div>

                    <label
                        v-else-if="!isReadOnly"
                        class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition-colors hover:bg-muted/50"
                        :class="
                            form.has_video
                                ? 'border-chart-2/40 bg-chart-2/5'
                                : ''
                        "
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted"
                            :class="
                                form.has_video
                                    ? 'bg-chart-2/15 text-chart-2'
                                    : ''
                            "
                        >
                            <Video class="size-4" />
                        </span>
                        <span class="flex-1">
                            <span class="block text-sm font-medium"
                                >Add a video room</span
                            >
                            <span class="block text-xs text-muted-foreground"
                                >Create a Jitsi room for this event</span
                            >
                        </span>
                        <span
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors"
                            :class="form.has_video ? 'bg-chart-2' : 'bg-input'"
                        >
                            <span
                                class="inline-block size-4 transform rounded-full bg-background shadow transition-transform"
                                :class="
                                    form.has_video
                                        ? 'translate-x-6'
                                        : 'translate-x-1'
                                "
                            />
                        </span>
                        <input
                            v-model="form.has_video"
                            type="checkbox"
                            class="sr-only"
                        />
                    </label>
                    <DialogFooter
                        class="flex-col gap-3 pt-4 sm:flex-row sm:justify-between"
                    >
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <Button
                                v-if="isEdit && event?.has_video"
                                type="button"
                                variant="outline"
                                class="w-full text-chart-2 hover:text-chart-2 sm:w-auto"
                                @click="joinRoom"
                            >
                                <Video class="mr-2 size-4" />
                                Join room
                            </Button>

                            <Button
                                v-if="canRemove"
                                type="button"
                                variant="outline"
                                class="w-full text-destructive hover:text-destructive sm:w-auto"
                                @click="removeEvent"
                            >
                                <Trash class="mr-2 size-4" />
                                Remove
                            </Button>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <template v-if="isReadOnly">
                                <template
                                    v-if="
                                        myParticipationStatus === 'needs_action'
                                    "
                                >
                                    <Button
                                        type="button"
                                        class="w-full sm:w-auto"
                                        :disabled="isResponding"
                                        @click="respondToEvent('accepted')"
                                    >
                                        <Check class="mr-2 size-4" />
                                        Accept
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="w-full text-muted-foreground hover:text-foreground sm:w-auto"
                                        :disabled="isResponding"
                                        @click="respondToEvent('declined')"
                                    >
                                        <X class="mr-2 size-4" />
                                        Decline
                                    </Button>
                                </template>

                                <template
                                    v-else-if="
                                        myParticipationStatus === 'declined'
                                    "
                                >
                                    <Button
                                        type="button"
                                        class="w-full sm:w-auto"
                                        :disabled="isResponding"
                                        @click="respondToEvent('accepted')"
                                    >
                                        <Check class="mr-2 size-4" />
                                        Accept invitation
                                    </Button>
                                </template>

                                <template
                                    v-else-if="
                                        myParticipationStatus === 'accepted'
                                    "
                                >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="w-full text-muted-foreground hover:text-foreground sm:w-auto"
                                        :disabled="isResponding"
                                        @click="respondToEvent('declined')"
                                    >
                                        <X class="mr-2 size-4" />
                                        Decline invitation
                                    </Button>
                                </template>
                            </template>

                            <Button
                                type="button"
                                variant="outline"
                                class="w-full sm:w-auto"
                                @click="isOpen = false"
                            >
                                {{ isReadOnly ? 'Close' : 'Cancel' }}
                            </Button>

                            <Button
                                v-if="!isReadOnly"
                                type="submit"
                                :disabled="form.processing"
                                class="w-full sm:w-auto"
                            >
                                <template v-if="isEdit">
                                    <Save class="mr-2 size-4" />Save changes
                                </template>
                                <template v-else>
                                    <UserPlus class="mr-2 size-4" />Create event
                                </template>
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </div>
        </DialogContent>
    </Dialog>
</template>
