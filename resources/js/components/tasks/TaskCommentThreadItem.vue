<script setup lang="ts">
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import InputError from '@/components/InputError.vue';
import TaskRichTextEditor from '@/components/tasks/TaskRichTextEditor.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { TaskComment } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { MoreHorizontal, Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
    updateComment: [commentId: number, body: string];
    deleteComment: [commentId: number];
}>();

const page = usePage();
const isOwner = computed(() => props.comment.user.id === page.props.auth.user?.id);

const isEditing = ref(false);
const editBody = ref('');
const showDeleteDialog = ref(false);

const startEdit = () => {
    editBody.value = props.comment.body;
    isEditing.value = true;
};

const cancelEdit = () => {
    isEditing.value = false;
    editBody.value = '';
};

const submitEdit = () => {
    const bodyText = editBody.value.replace(/<[^>]*>/g, ' ').trim();
    if (!bodyText) return;
    emit('updateComment', props.comment.id, editBody.value);
    isEditing.value = false;
};

const handleDelete = () => {
    showDeleteDialog.value = true;
};

const isReplying = () => props.activeReplyToId === props.comment.id;
const repliesExpanded = ref(false);
const allReplies = computed(() => props.comment.replies ?? []);
const canToggleReplies = computed(() => props.depth === 0);
const visibleReplies = computed(() => {
    if (!canToggleReplies.value) {
        return allReplies.value;
    }

    return repliesExpanded.value ? allReplies.value : [];
});

const showMoreReplies = () => {
    repliesExpanded.value = true;
};

const showLessReplies = () => {
    repliesExpanded.value = false;
};
</script>

<template>
    <div
        class="space-y-3"
        :class="depth > 0 ? 'ml-5 border-l border-border/60 pl-4' : ''"
    >
        <div class="rounded-lg border border-border bg-background p-4">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
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

                <div v-if="isOwner && !isEditing">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="h-6 w-6 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                            >
                                <MoreHorizontal class="size-3.5" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-32">
                            <DropdownMenuItem @click="startEdit">
                                <Pencil class="mr-2 size-3" />
                                <span>Edit</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                @click="handleDelete"
                                class="text-destructive focus:text-destructive focus:bg-destructive/10"
                            >
                                <Trash2 class="mr-2 size-3" />
                                <span>Delete</span>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <div v-if="isEditing" class="space-y-2 mt-2">
                <TaskRichTextEditor
                    :model-value="editBody"
                    placeholder="Edit comment..."
                    @update:model-value="editBody = $event"
                />
                <div class="flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-8 text-xs"
                        @click="cancelEdit"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        class="h-8 text-xs"
                        @click="submitEdit"
                    >
                        Save
                    </Button>
                </div>
            </div>
            <div
                v-else
                class="comment-rich-text text-sm leading-relaxed text-foreground"
                v-html="comment.body"
            ></div>

            <div class="mt-3 flex items-center gap-2">
                <Button
                    v-if="!isReplying() && !isEditing"
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-8 px-2 text-xs"
                    @click="emit('startReply', comment.id)"
                >
                    Reply
                </Button>
                <Button
                    v-if="isReplying()"
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-8 px-2 text-xs"
                    @click="emit('cancelReply')"
                >
                    Cancel
                </Button>



                <Button
                    v-if="
                        canToggleReplies &&
                        allReplies.length > 0 &&
                        !repliesExpanded
                    "
                    type="button"
                    variant="link"
                    size="sm"
                    class="h-8 px-2 text-xs text-muted-foreground"
                    @click="showMoreReplies"
                >
                    Show {{ allReplies.length }}
                    {{ allReplies.length === 1 ? 'reply' : 'replies' }}
                </Button>

                <Button
                    v-if="
                        canToggleReplies &&
                        allReplies.length > 0 &&
                        repliesExpanded
                    "
                    type="button"
                    variant="link"
                    size="sm"
                    class="h-8 px-2 text-xs text-muted-foreground"
                    @click="showLessReplies"
                >
                    Hide replies
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
                        @click="() => { emit('submitReply', comment.id); repliesExpanded = true; }"
                    >
                        Post reply
                    </Button>
                </div>
            </div>
        </div>

        <div v-if="allReplies.length > 0" class="space-y-3">
            <TaskCommentThreadItem
                v-for="reply in visibleReplies"
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
                @update-comment="(commentId, body) => emit('updateComment', commentId, body)"
                @delete-comment="(commentId) => emit('deleteComment', commentId)"
            />
        </div>

        <ConfirmDeleteDialog
            v-model:open="showDeleteDialog"
            title="Delete comment"
            description="Are you sure you want to delete this comment? This action cannot be undone."
            @confirm="emit('deleteComment', comment.id)"
        />
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
