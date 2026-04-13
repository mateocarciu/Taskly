<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TaskRichTextEditor from '@/components/tasks/TaskRichTextEditor.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import type { TaskComment } from '@/types';

defineOptions({
    name: 'TaskCommentThreadItem',
});

const props = withDefaults(
    defineProps<{
        comment: TaskComment;
        depth?: number;
        activeReplyToId: number | null;
        replyBody: string;
        replyError?: string;
        postingReply: boolean;
        formatDate: (value: string) => string;
        getInitials: (value: string) => string;
    }>(),
    {
        depth: 0,
        replyError: undefined,
    },
);

const emit = defineEmits<{
    startReply: [commentId: number];
    cancelReply: [];
    updateReplyBody: [value: string];
    submitReply: [commentId: number];
}>();

const isReplying = () => props.activeReplyToId === props.comment.id;
</script>

<template>
    <div
        class="space-y-3"
        :class="depth > 0 ? 'ml-5 border-l border-border/60 pl-4' : ''"
    >
        <div class="rounded-lg border border-border bg-background p-4">
            <div class="mb-3 flex items-start gap-3">
                <Avatar class="h-8 w-8 border border-border">
                    <AvatarFallback class="text-xs font-semibold">
                        {{ getInitials(comment.user.name) }}
                    </AvatarFallback>
                </Avatar>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-medium">
                            {{ comment.user.name }}
                        </p>
                        <span class="text-xs text-muted-foreground">
                            {{ formatDate(comment.created_at) }}
                        </span>
                    </div>
                </div>
            </div>

            <div
                class="comment-rich-text text-sm leading-relaxed text-foreground"
                v-html="comment.body"
            ></div>

            <div class="mt-3 flex items-center gap-2">
                <Button
                    v-if="!isReplying()"
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-8 px-2 text-xs"
                    @click="emit('startReply', comment.id)"
                >
                    Reply
                </Button>
                <Button
                    v-else
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-8 px-2 text-xs"
                    @click="emit('cancelReply')"
                >
                    Cancel
                </Button>
            </div>
        </div>

        <div
            v-if="isReplying()"
            class="rounded-lg border border-border/70 bg-muted/20 p-3"
        >
            <div class="space-y-2">
                <TaskRichTextEditor
                    :model-value="replyBody"
                    placeholder="Write a reply..."
                    @update:model-value="emit('updateReplyBody', $event)"
                />
                <InputError :message="replyError" />
                <div class="flex justify-end">
                    <Button
                        type="button"
                        size="sm"
                        :disabled="postingReply"
                        @click="emit('submitReply', comment.id)"
                    >
                        Post reply
                    </Button>
                </div>
            </div>
        </div>

        <div v-if="(comment.replies?.length ?? 0) > 0" class="space-y-3">
            <TaskCommentThreadItem
                v-for="reply in comment.replies"
                :key="reply.id"
                :comment="reply"
                :depth="depth + 1"
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

<style scoped>
.comment-rich-text :deep(p) {
    margin: 0;
}

.comment-rich-text :deep(p + p) {
    margin-top: 0.25rem;
}

.comment-rich-text :deep(ul),
.comment-rich-text :deep(ol) {
    margin: 0.25rem 0 0 1rem;
    padding: 0;
}

.comment-rich-text :deep(ul) {
    list-style: disc;
}

.comment-rich-text :deep(ol) {
    list-style: decimal;
}

.comment-rich-text :deep(pre) {
    margin-top: 0.25rem;
    overflow-x: auto;
}
</style>
