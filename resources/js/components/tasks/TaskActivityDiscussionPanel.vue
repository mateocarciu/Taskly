<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TaskCommentThreadItem from '@/components/tasks/TaskCommentThreadItem.vue';
import TaskRichTextEditor from '@/components/tasks/TaskRichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { Task, TaskComment, TaskEvent } from '@/types';
import { AlertTriangle, Calendar, Check, ClockAlert, MessageSquarePlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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

    return props.formatDate(props.activeTask.due_date);
});

const daysInColumnLabel = computed(() => {
    const days = props.activeTask?.days_in_column;

    if (days === null || days === undefined) return null;
    return days === 0 ? 'Today' : `${days}d`;
});

const isDone = computed(() => props.activeTask?.column?.type === 'done');

const activeTab = ref<'discussion' | 'activity'>('discussion');
</script>

<template>
    <div
        class="flex flex-col border-t border-border lg:h-full lg:min-h-0 lg:border-t-0 lg:border-l"
    >
        <div class="px-6 pt-6 lg:pl-8">
            <div
                v-if="isLoadingDetails"
                class="mb-6 flex items-center gap-2 rounded-lg border border-border/70 bg-muted/20 px-3 py-2 text-sm text-muted-foreground"
            >
                <Spinner class="size-4" />
                Loading task details...
            </div>

            <div class="mb-6 space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div
                        v-if="props.activeTask?.column"
                        class="flex items-center gap-1.5 rounded-md border border-border/50 bg-muted/20 px-2 py-0.5"
                    >
                        <span class="text-xs font-medium text-muted-foreground">{{ props.activeTask.column.name }}</span>
                        <Check v-if="isDone" class="size-3 text-green-500" />
                    </div>

                    <div
                        v-if="dueDateLabel"
                        :class="[
                            'flex items-center gap-1.5 transition-colors',
                            !isDone && isOverdue
                                ? 'bg-destructive/10 text-destructive border border-destructive/20 px-2 py-0.5 rounded-md font-semibold text-[11px]'
                                : 'text-xs text-muted-foreground font-medium'
                        ]"
                        title="Due date"
                    >
                        <AlertTriangle v-if="!isDone && isOverdue" class="size-3.5" />
                        <Calendar v-else class="size-3.5" />
                        <span>{{ dueDateLabel }}</span>
                    </div>

                    <div
                        v-if="daysInColumnLabel"
                        class="flex items-center gap-1.5 text-xs text-muted-foreground font-medium"
                        title="Time in column"
                    >
                        <ClockAlert class="size-3.5 text-muted-foreground" />
                        <span>{{ daysInColumnLabel }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-6 border-b border-border/60">
                    <button
                        type="button"
                        @click="activeTab = 'discussion'"
                        :class="[
                            'pb-2 text-sm font-medium transition-colors border-b-2',
                            activeTab === 'discussion' 
                                ? 'border-primary text-foreground' 
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        ]"
                    >
                        Discussion
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'activity'"
                        :class="[
                            'pb-2 text-sm font-medium transition-colors border-b-2',
                            activeTab === 'activity' 
                                ? 'border-primary text-foreground' 
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        ]"
                    >
                        Activity
                    </button>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-6 pb-6 lg:pl-8">
            <!-- Activity Tab -->
            <div v-show="activeTab === 'activity'" class="space-y-4">
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

            <!-- Discussion Tab -->
            <div v-show="activeTab === 'discussion'" class="space-y-6">
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

                <div class="space-y-3">
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
        </div>
    </div>
</template>
