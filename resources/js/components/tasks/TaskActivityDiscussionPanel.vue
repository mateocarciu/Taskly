<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TaskCommentThreadItem from '@/components/tasks/TaskCommentThreadItem.vue';
import TaskRichTextEditor from '@/components/tasks/TaskRichTextEditor.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { Task, TaskComment, TaskEvent } from '@/types';
import { Calendar, ClockAlert, MessageSquarePlus } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    isLoadingDetails: boolean;
    timelineEvents: TaskEvent[];
    getEventLabel: (event: TaskEvent) => string;
    formatTimelineDate: (value: string) => string;
    activeTask: Task | null;
    isOverdue: boolean;
    commentsList: TaskComment[];
    commentBody: string;
    commentError?: string;
    commentProcessing: boolean;
    activeReplyToId: number | null;
    replyBody: string;
    replyError?: string;
    postingReply: boolean;
    formatDate: (value: string) => string;
    getInitials: (value: string) => string;
}>();

const emit = defineEmits<{
    submitComment: [];
    updateCommentBody: [value: string];
    startReply: [commentId: number];
    cancelReply: [];
    updateReplyBody: [value: string];
    submitReply: [commentId: number];
}>();

const cleanedCommentBody = computed(() => {
    return props.commentBody
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
});

const dueDateLabel = computed(() => {
    if (!props.activeTask?.due_date) return null;

    return new Date(props.activeTask.due_date).toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'short',
    });
});

const daysInColumnLabel = computed(() => {
    const days = props.activeTask?.days_in_column;

    if (days === null || days === undefined) return null;
    return days === 0 ? 'Today' : `${days}d`;
});
</script>

<template>
    <div
        class="border-t border-border p-6 pt-4 xl:overflow-y-auto xl:overscroll-contain xl:border-t-0 xl:border-l xl:pl-8"
    >
        <div
            v-if="isLoadingDetails"
            class="mb-6 flex items-center gap-2 rounded-lg border border-border/70 bg-muted/20 px-3 py-2 text-sm text-muted-foreground"
        >
            <Spinner class="size-4" />
            Loading task details...
        </div>

        <div class="mb-6 space-y-3">
            <h3 class="text-base font-semibold">Activity</h3>

            <div
                v-if="timelineEvents.length === 0"
                class="rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground"
            >
                No activity yet.
            </div>

            <ol v-else class="space-y-2">
                <li
                    v-for="event in timelineEvents"
                    :key="event.id"
                    class="rounded-lg border border-border/70 bg-muted/20 px-3 py-2 text-sm"
                >
                    <p class="font-medium text-foreground">
                        {{ getEventLabel(event) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ formatTimelineDate(event.created_at) }}
                    </p>
                </li>
            </ol>
        </div>

        <div class="mb-4 space-y-3">
            <h3 class="text-base font-semibold">Discussion</h3>
            <div
                class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
            >
                <Badge
                    v-if="dueDateLabel"
                    :variant="isOverdue ? 'destructive' : 'secondary'"
                    class="gap-1"
                >
                    <Calendar class="size-3" />
                    {{ dueDateLabel }}
                </Badge>
                <Badge
                    v-if="daysInColumnLabel"
                    variant="secondary"
                    class="gap-1"
                >
                    <ClockAlert class="size-3" />
                    {{ daysInColumnLabel }}
                </Badge>
            </div>
        </div>

        <form class="space-y-3" @submit.prevent="emit('submitComment')">
            <div class="grid gap-2">
                <Label>Leave a comment</Label>
                <TaskRichTextEditor
                    :model-value="commentBody"
                    placeholder="Share progress, blockers, or next steps..."
                    @update:model-value="emit('updateCommentBody', $event)"
                />
                <InputError :message="commentError" />
            </div>

            <Button
                type="submit"
                :disabled="commentProcessing || !cleanedCommentBody"
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
                :reply-body="replyBody"
                :reply-error="replyError"
                :posting-reply="postingReply"
                :format-date="formatDate"
                :get-initials="getInitials"
                @start-reply="emit('startReply', $event)"
                @cancel-reply="emit('cancelReply')"
                @update-reply-body="emit('updateReplyBody', $event)"
                @submit-reply="emit('submitReply', $event)"
            />
        </div>
    </div>
</template>
