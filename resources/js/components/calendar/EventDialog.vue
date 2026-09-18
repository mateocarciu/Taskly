<script setup lang="ts">
import { computed, watch } from 'vue';
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
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import DateTimeField from '@/components/calendar/DateTimeField.vue';
import { store, update, destroy, room } from '@/routes/calendar/events';
import type { CalendarEvent, TeamMember } from '@/types';
import { Check, EyeOff, UserPlus, Video, X } from '@lucide/vue';

const props = defineProps<{
    teamMembers: TeamMember[];
    defaultStart?: string | null;
    defaultEnd?: string | null;
    event?: CalendarEvent | null;
}>();

const isOpen = defineModel<boolean>('open', { default: false });

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
const isOwner = computed(() => props.event?.organizer?.id === currentUserId);
const isReadOnly = computed(() => isEdit.value && !isOwner.value);

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

watch(isOpen, () => {
    form.clearErrors();
    if (props.event) {
        fillFromEvent();
    } else {
        form.reset();
        applyDefaultStart();
    }
});

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
                <form class="space-y-5 px-6 pt-4 pb-6" @submit.prevent="submit">
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
                    <div v-if="teamMembers.length" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label>Invite teammates</Label
                            ><span class="text-xs text-muted-foreground"
                                >{{ form.attendee_ids.length }} selected</span
                            >
                        </div>
                        <div
                            class="grid max-h-36 gap-1 overflow-y-auto rounded-xl border p-2 sm:grid-cols-2"
                        >
                            <label
                                v-for="member in teamMembers"
                                :key="member.id"
                                class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-sm transition-colors hover:bg-muted"
                                :class="[
                                    form.attendee_ids.includes(member.id)
                                        ? 'bg-primary/8 text-primary'
                                        : '',
                                    isReadOnly ? 'cursor-default' : '',
                                ]"
                            >
                                <span
                                    class="flex size-5 items-center justify-center rounded border"
                                    :class="
                                        form.attendee_ids.includes(member.id)
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
                                    :disabled="isReadOnly"
                                />
                                <span class="truncate">{{ member.name }}</span>
                            </label>
                        </div>
                    </div>
                    <label
                        class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition-colors hover:bg-muted/50"
                        :class="[
                            form.has_video
                                ? 'border-primary/40 bg-primary/4'
                                : '',
                            isReadOnly ? 'cursor-default' : '',
                        ]"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted"
                            :class="
                                form.has_video
                                    ? 'bg-violet-500/10 text-violet-600'
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
                            :class="form.has_video ? 'bg-primary' : 'bg-input'"
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
                            :disabled="isReadOnly"
                        />
                    </label>
                    <DialogFooter class="pt-4">
                        <template v-if="isEdit && event?.has_video">
                            <Button
                                type="button"
                                variant="outline"
                                class="text-violet-600 hover:text-violet-600"
                                @click="joinRoom"
                            >
                                <Video class="mr-2 size-4" />
                                Join room
                            </Button>
                        </template>

                        <template v-if="isReadOnly">
                            <Button
                                type="button"
                                variant="outline"
                                class="text-destructive hover:text-destructive"
                                @click="removeEvent"
                                ><EyeOff class="mr-2 size-4" />Remove from my
                                calendar</Button
                            >
                            <Button
                                type="button"
                                variant="outline"
                                @click="isOpen = false"
                                >Close</Button
                            >
                        </template>

                        <template v-else-if="isEdit">
                            <Button
                                type="button"
                                variant="outline"
                                class="text-destructive hover:text-destructive"
                                @click="removeEvent"
                                ><EyeOff class="mr-2 size-4" />Remove from my
                                calendar</Button
                            >
                            <Button
                                type="button"
                                variant="outline"
                                @click="isOpen = false"
                                >Cancel</Button
                            >
                            <Button type="submit" :disabled="form.processing"
                                ><Check class="mr-2 size-4" />Save
                                changes</Button
                            >
                        </template>

                        <template v-else>
                            <Button
                                type="button"
                                variant="outline"
                                @click="isOpen = false"
                                >Cancel</Button
                            >
                            <Button type="submit" :disabled="form.processing"
                                ><UserPlus class="mr-2 size-4" />Create
                                event</Button
                            >
                        </template>
                    </DialogFooter>
                </form>
            </div>
        </DialogContent>
    </Dialog>
</template>
