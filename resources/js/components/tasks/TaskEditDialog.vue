<script setup lang="ts">
import TaskActivityDiscussionPanel from '@/components/tasks/TaskActivityDiscussionPanel.vue';
import TaskEditFormPanel from '@/components/tasks/TaskEditFormPanel.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useInitials } from '@/composables/useInitials';
import { show, update } from '@/routes/tasks';
import comments from '@/routes/tasks/comments';
import type { Task, TaskComment, TaskEvent, TeamMember } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    task: Task | null;
    teamMembers: TeamMember[];
}>();

const { getInitials } = useInitials();
const isOpen = defineModel<boolean>('open', { default: false });
const taskDetails = ref<Task | null>(null);
const isLoadingDetails = ref(false);
const commentsList = ref<TaskComment[]>([]);

const activeTask = computed(() => taskDetails.value ?? props.task);

const form = useForm({
    title: '',
    description: '',
    due_date: '',
    assigned_to: null as number | null,
});

const commentForm = useForm({
    body: '',
});

const replyForm = useForm({
    body: '',
    parent_id: null as number | null,
});

const activeReplyToId = ref<number | null>(null);

const stripRichText = (value: string) => {
    return value
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
};

const hydrateFormsFromTask = (task: Task) => {
    form.title = task.title;
    form.description = task.description || '';
    form.due_date = task.due_date
        ? new Date(task.due_date).toISOString().slice(0, 16)
        : '';
    form.assigned_to = task.assigned_to ?? null;
    commentsList.value = [...(task.comments ?? [])];
};

const loadTaskDetails = async (taskId: number) => {
    isLoadingDetails.value = true;

    try {
        const response = await fetch(show(taskId).url, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Could not load task details');
        }

        const task = (await response.json()) as Task;
        taskDetails.value = task;
        hydrateFormsFromTask(task);
        return true;
    } catch {
        toast.error('Unable to load task details');
        return false;
    } finally {
        isLoadingDetails.value = false;
    }
};

const isOverdue = computed(() => {
    return activeTask.value?.due_date
        ? new Date(activeTask.value.due_date) < new Date()
        : false;
});

const formatDate = (value: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const formatTimelineDate = (value: string) => {
    return new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const timelineEvents = computed(() => {
    return [...(activeTask.value?.events ?? [])].sort((a, b) => {
        return (
            new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
        );
    });
});

const getEventLabel = (event: TaskEvent) => {
    const actorName = event.actor?.name ?? 'Someone';

    if (event.type === 'created') {
        return `Created by ${actorName}`;
    }

    if (event.type === 'moved') {
        const destination =
            (event.metadata?.to_column_name as string | undefined) ??
            'another column';

        return `Moved to ${destination} by ${actorName}`;
    }

    if (event.type === 'assigned') {
        const assignedName = event.metadata?.assigned_to_name as
            | string
            | undefined;

        if (assignedName) {
            return `Assigned to ${assignedName} by ${actorName}`;
        }

        return `Unassigned by ${actorName}`;
    }

    return `Updated by ${actorName}`;
};

const submit = () => {
    if (!activeTask.value) return;

    form.put(update(activeTask.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
            toast.success('Task updated');
        },
    });
};

const submitComment = () => {
    if (!activeTask.value) return;
    const taskId = activeTask.value.id;

    const bodyText = stripRichText(commentForm.body);

    if (!bodyText) return;

    commentForm.post(comments.store(taskId).url, {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset('body');
            void loadTaskDetails(taskId);
            toast.success('Comment posted');
        },
    });
};

const startReply = (commentId: number) => {
    activeReplyToId.value = commentId;
    replyForm.parent_id = commentId;
    replyForm.reset('body');
    replyForm.clearErrors();
};

const cancelReply = () => {
    activeReplyToId.value = null;
    replyForm.reset('body');
    replyForm.clearErrors();
    replyForm.parent_id = null;
};

const submitReply = (commentId: number) => {
    if (!activeTask.value) return;
    const taskId = activeTask.value.id;

    const bodyText = stripRichText(replyForm.body);
    if (!bodyText) return;

    replyForm.parent_id = commentId;
    replyForm.post(comments.store(taskId).url, {
        preserveScroll: true,
        onSuccess: () => {
            cancelReply();
            void loadTaskDetails(taskId);
            toast.success('Reply posted');
        },
    });
};

watch([() => props.task, isOpen], ([task, open]) => {
    if (task && open) {
        taskDetails.value = null;
        hydrateFormsFromTask(task);
        void loadTaskDetails(task.id);
        form.clearErrors();
        commentForm.reset('body');
        commentForm.clearErrors();
        cancelReply();
    }

    if (!open) {
        taskDetails.value = null;
        commentsList.value = [];
    }
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent
            class="task-edit-dialog w-[97vw] max-w-[1400px] overflow-hidden bg-background p-0 sm:max-w-[1400px]"
            :show-close-button="false"
            @open-auto-focus.prevent
        >
            <div
                class="max-h-[92vh] overflow-y-auto overscroll-contain bg-background xl:overflow-hidden"
            >
                <DialogHeader
                    class="sticky top-0 z-20 border-b bg-background/95 px-6 pt-6 pr-14 pb-4 backdrop-blur supports-backdrop-filter:bg-background/80"
                >
                    <DialogTitle>Task details</DialogTitle>
                    <DialogDescription>
                        Update the task and keep the discussion in one place.
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

                <div
                    class="grid xl:h-[calc(92vh-96px)] xl:grid-cols-[minmax(0,1.1fr)_minmax(420px,0.9fr)]"
                >
                    <TaskEditFormPanel
                        :form="form"
                        :team-members="teamMembers"
                        :is-loading-details="isLoadingDetails"
                        @submit="submit"
                        @cancel="isOpen = false"
                        @update:title="form.title = $event"
                        @update:description="form.description = $event"
                        @update:due-date="form.due_date = $event"
                        @update:assigned-to="form.assigned_to = $event"
                    />

                    <TaskActivityDiscussionPanel
                        :is-loading-details="isLoadingDetails"
                        :timeline-events="timelineEvents"
                        :get-event-label="getEventLabel"
                        :format-timeline-date="formatTimelineDate"
                        :active-task="activeTask"
                        :is-overdue="isOverdue"
                        :comments-list="commentsList"
                        :comment-body="commentForm.body"
                        :comment-error="commentForm.errors.body"
                        :comment-processing="commentForm.processing"
                        :active-reply-to-id="activeReplyToId"
                        :reply-body="replyForm.body"
                        :reply-error="replyForm.errors.body"
                        :posting-reply="replyForm.processing"
                        :format-date="formatDate"
                        :get-initials="getInitials"
                        @submit-comment="submitComment"
                        @update-comment-body="commentForm.body = $event"
                        @start-reply="startReply"
                        @cancel-reply="cancelReply"
                        @update-reply-body="replyForm.body = $event"
                        @submit-reply="submitReply"
                    />
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
