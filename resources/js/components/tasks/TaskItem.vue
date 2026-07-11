<script setup lang="ts">
import TagBadge from '@/components/tags/TagBadge.vue';
import TaskDeleteDialog from '@/components/tasks/TaskDeleteDialog.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/composables/useInitials';
import { destroy } from '@/routes/tasks';
import type { Task } from '@/types';
import { router } from '@inertiajs/vue3';
import { AlertTriangle, Calendar, Check, ClockAlert, MoreHorizontal, Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { formatDate } from '@/composables/useDateFormatter';

const props = withDefaults(
    defineProps<{
        task: Task;
        isDone?: boolean;
    }>(),
    {
        isDone: false,
    }
);

const emit = defineEmits<{
    edit: [task: Task];
}>();

const { getInitials } = useInitials();

const showDeleteDialog = ref(false);

const isOverdue = (task: Task) => {
    if (props.isDone) return false;
    return task.due_date ? new Date(task.due_date) < new Date() : false;
};

const deleteTask = () => {
    router.delete(destroy(props.task.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false;
            toast.success('Task deleted');
        },
    });
};
</script>

<template>
    <Card
        @click.stop="emit('edit', task)"
        class="group relative cursor-pointer border-border transition-all hover:border-primary/40 hover:bg-accent/5 hover:shadow-sm"
    >
        <CardContent class="flex flex-col gap-3 pt-3.5">
            <!-- Header: Title and Actions -->
            <div class="flex items-start justify-between gap-2">
                <div class="flex min-w-0 items-start gap-1.5">
                    <p
                        class="line-clamp-2 text-sm leading-snug font-medium"
                        :title="task.title"
                    >
                        {{ task.title }}
                    </p>
                </div>
                <div
                    class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="h-6 w-6 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                                @click.stop
                                title="Task actions"
                            >
                                <MoreHorizontal class="size-3.5" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-32">
                            <DropdownMenuItem @click.stop="emit('edit', task)">
                                <Pencil class="mr-2 size-3" />
                                <span>Edit</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                @click.stop="showDeleteDialog = true"
                                class="text-destructive focus:text-destructive focus:bg-destructive/10"
                            >
                                <Trash2 class="mr-2 size-3" />
                                <span>Delete</span>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <!-- Description Preview -->
            <div
                v-if="task.description"
                class="min-w-0 overflow-hidden text-[12px] leading-relaxed text-muted-foreground"
            >
                <div
                    class="task-description-preview line-clamp-3"
                    v-html="task.description"
                ></div>
            </div>

            <!-- Tags Display -->
            <div v-if="task.tags && task.tags.length > 0" class="mt-1">
                <TagBadge :tags="task.tags" />
            </div>

            <!-- Footer: Meta tags & Avatar -->
            <div
                class="mt-2 pt-2.5 border-t border-border/40 flex items-center justify-between gap-2"
            >
                <div class="flex flex-wrap items-center gap-3">
                    <div
                        v-if="
                            task.days_in_column !== null &&
                            task.days_in_column !== undefined
                        "
                        class="flex items-center gap-1.5 text-[11px] text-muted-foreground/80 font-medium"
                        title="Time in column"
                    >
                        <ClockAlert class="size-3.5 text-muted-foreground/80" />
                        <span>{{
                            task.days_in_column === 0
                                ? 'Today'
                                : `${task.days_in_column}d`
                        }}</span>
                    </div>

                    <div
                        v-if="task.due_date"
                        :class="[
                            'flex items-center gap-1.5 transition-colors',
                            !isDone && isOverdue(task)
                                ? 'bg-destructive/10 text-destructive border border-destructive/20 px-2 py-0.5 rounded-md font-semibold text-[10px]'
                                : 'text-[11px] text-muted-foreground/80 font-medium'
                        ]"
                        title="Due date"
                    >
                        <AlertTriangle v-if="!isDone && isOverdue(task)" class="size-3.5" />
                        <Calendar v-else class="size-3.5" />
                        <span>{{ formatDate(task.due_date) }}</span>
                    </div>

                    <div
                        v-if="isDone"
                        class="flex items-center gap-1 text-[11px] font-medium text-green-500 dark:text-green-400"
                        title="Done"
                    >
                        <Check class="size-3.5" />
                    </div>
                </div>

                <!-- Avatar -->
                <div
                    v-if="task.assignee"
                    class="shrink-0"
                    :title="`Assigned to ${task.assignee.name}`"
                >
                    <Avatar class="h-6 w-6 border border-background shadow-sm">
                        <AvatarFallback
                            class="bg-primary/10 text-[9px] font-semibold text-primary"
                        >
                            {{ getInitials(task.assignee.name) }}
                        </AvatarFallback>
                    </Avatar>
                </div>
                <div v-else class="shrink-0" title="Unassigned task">
                    <Avatar class="h-6 w-6 border border-background shadow-sm">
                        <AvatarFallback
                            class="bg-muted/10 text-[9px] font-semibold text-muted-foreground"
                        >
                            --
                        </AvatarFallback>
                    </Avatar>
                </div>
            </div>
        </CardContent>
    </Card>

    <TaskDeleteDialog
        v-model:open="showDeleteDialog"
        :task="task"
        @confirm="deleteTask"
    />
</template>

<style scoped>
.task-description-preview {
    font-size: 12px;
}

.task-description-preview :deep(p) {
    margin: 0;
}

.task-description-preview :deep(p + p) {
    margin-top: 0.25rem;
}

.task-description-preview :deep(ul),
.task-description-preview :deep(ol) {
    margin: 0.25rem 0 0 1rem;
    padding: 0;
}

.task-description-preview :deep(ul) {
    list-style: disc;
}

.task-description-preview :deep(ol) {
    list-style: decimal;
}

.task-description-preview :deep(a) {
    pointer-events: none;
    text-decoration: none;
}
</style>
