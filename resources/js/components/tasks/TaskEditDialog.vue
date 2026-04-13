<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TaskAssigneeSelect from '@/components/tasks/TaskAssigneeSelect.vue';
import TaskCommentThreadItem from '@/components/tasks/TaskCommentThreadItem.vue';
import TaskRichTextEditor from '@/components/tasks/TaskRichTextEditor.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useInitials } from '@/composables/useInitials';
import { update } from '@/routes/tasks';
import comments from '@/routes/tasks/comments';
import type { AppPageProps, Task, TaskComment, TeamMember } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    ClockAlert,
    MessageSquarePlus,
    Save,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    task: Task | null;
    teamMembers: TeamMember[];
}>();

const page = usePage<AppPageProps>();
const { getInitials } = useInitials();
const isOpen = defineModel<boolean>('open', { default: false });
const commentsList = ref<TaskComment[]>([]);

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

const syncCommentsFromLatestPage = () => {
    if (!props.task) return false;

    const pageColumns = ((page.props as unknown as { columns?: unknown[] })
        .columns ?? []) as Array<{ tasks?: Task[] }>;

    for (const column of pageColumns) {
        const foundTask = column.tasks?.find((candidate) => {
            return candidate.id === props.task?.id;
        });

        if (foundTask) {
            commentsList.value = [...(foundTask.comments ?? [])];
            return true;
        }
    }

    return false;
};

const isOverdue = computed(() => {
    return props.task?.due_date
        ? new Date(props.task.due_date) < new Date()
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

const submit = () => {
    if (!props.task) return;

    form.put(update(props.task.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
            toast.success('Task updated');
        },
    });
};

const submitComment = () => {
    if (!props.task) return;

    const bodyText = stripRichText(commentForm.body);

    if (!bodyText) return;

    commentForm.post(comments.store(props.task.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            syncCommentsFromLatestPage();
            commentForm.reset('body');
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
    if (!props.task) return;

    const bodyText = stripRichText(replyForm.body);
    if (!bodyText) return;

    replyForm.parent_id = commentId;
    replyForm.post(comments.store(props.task.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            syncCommentsFromLatestPage();
            cancelReply();
            toast.success('Reply posted');
        },
    });
};

watch([() => props.task, isOpen], ([task, open]) => {
    if (task && open) {
        form.title = task.title;
        form.description = task.description || '';
        form.due_date = task.due_date
            ? new Date(task.due_date).toISOString().slice(0, 16)
            : '';
        form.assigned_to = task.assigned_to ?? null;
        form.clearErrors();
        commentsList.value = [...(task.comments ?? [])];
        syncCommentsFromLatestPage();
        commentForm.reset('body');
        commentForm.clearErrors();
        cancelReply();
    }
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent
            class="task-edit-dialog w-[96vw] max-w-[1100px] overflow-hidden bg-background p-0 sm:max-w-[1100px]"
            :show-close-button="false"
            @open-auto-focus.prevent
        >
            <div
                class="max-h-[92vh] overflow-y-auto overscroll-contain bg-background"
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

                <div class="p-6">
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="edit-task-title">Title</Label>
                            <Input
                                id="edit-task-title"
                                type="text"
                                placeholder="Enter task title..."
                                v-model="form.title"
                            />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-task-description"
                                >Description</Label
                            >
                            <TaskRichTextEditor
                                v-model="form.description"
                                placeholder="Add more details..."
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="edit-task-due-date">Due date</Label>
                                <Input
                                    id="edit-task-due-date"
                                    type="datetime-local"
                                    v-model="form.due_date"
                                />
                                <InputError :message="form.errors.due_date" />
                            </div>

                            <div class="grid gap-2">
                                <Label>Assigned to</Label>
                                <TaskAssigneeSelect
                                    v-model="form.assigned_to"
                                    :team-members="teamMembers"
                                />
                                <InputError
                                    :message="form.errors.assigned_to"
                                />
                            </div>
                        </div>

                        <DialogFooter class="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                @click="isOpen = false"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                :disabled="!form.isDirty || form.processing"
                            >
                                <Spinner v-if="form.processing" />
                                <Save v-else class="size-4" />
                                Save changes
                            </Button>
                        </DialogFooter>
                    </form>
                </div>

                <div class="border-t border-border p-6 pt-4">
                    <div class="mb-4 space-y-3">
                        <h3 class="text-base font-semibold">Discussion</h3>
                        <div
                            class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                        >
                            <Badge
                                v-if="task?.due_date"
                                :variant="
                                    isOverdue ? 'destructive' : 'secondary'
                                "
                                class="gap-1"
                            >
                                <Calendar class="size-3" />
                                {{
                                    new Date(task.due_date).toLocaleDateString(
                                        'en-US',
                                        { day: 'numeric', month: 'short' },
                                    )
                                }}
                            </Badge>
                            <Badge
                                v-if="task?.days_in_column !== null"
                                variant="secondary"
                                class="gap-1"
                            >
                                <ClockAlert class="size-3" />
                                {{
                                    task?.days_in_column === 0
                                        ? 'Today'
                                        : `${task?.days_in_column}d`
                                }}
                            </Badge>
                        </div>
                    </div>

                    <form class="space-y-3" @submit.prevent="submitComment">
                        <div class="grid gap-2">
                            <Label>Leave a comment</Label>
                            <TaskRichTextEditor
                                v-model="commentForm.body"
                                placeholder="Share progress, blockers, or next steps..."
                            />
                            <InputError :message="commentForm.errors.body" />
                        </div>

                        <Button
                            type="submit"
                            :disabled="
                                commentForm.processing ||
                                !commentForm.body
                                    .replace(/<[^>]*>/g, ' ')
                                    .replace(/&nbsp;/g, ' ')
                                    .replace(/\s+/g, ' ')
                                    .trim()
                            "
                        >
                            <MessageSquarePlus class="size-4" />
                            Post comment
                        </Button>
                    </form>

                    <div class="mt-4 space-y-3">
                        <div
                            v-if="commentsList.length === 0"
                            class="rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground"
                        >
                            No comments yet. Start the discussion.
                        </div>

                        <TaskCommentThreadItem
                            v-for="comment in commentsList"
                            :key="comment.id"
                            :comment="comment"
                            :active-reply-to-id="activeReplyToId"
                            :reply-body="replyForm.body"
                            :reply-error="replyForm.errors.body"
                            :posting-reply="replyForm.processing"
                            :format-date="formatDate"
                            :get-initials="getInitials"
                            @start-reply="startReply"
                            @cancel-reply="cancelReply"
                            @update-reply-body="replyForm.body = $event"
                            @submit-reply="submitReply"
                        />
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
