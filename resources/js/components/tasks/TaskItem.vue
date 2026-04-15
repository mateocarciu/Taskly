<script setup lang="ts">
import TaskDeleteDialog from '@/components/tasks/TaskDeleteDialog.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useInitials } from '@/composables/useInitials';
import { destroy } from '@/routes/tasks';
import type { Task } from '@/types';
import { router } from '@inertiajs/vue3';
import { Calendar, ClockAlert, Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    task: Task;
}>();

const emit = defineEmits<{
    edit: [task: Task];
}>();

const { getInitials } = useInitials();

const showDeleteDialog = ref(false);

const isOverdue = (task: Task) => {
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
        <CardContent class="flex flex-col gap-3 p-3.5">
            <!-- Header: Title and Actions -->
            <div class="flex items-start justify-between gap-2">
                <p
                    class="line-clamp-2 text-sm leading-snug font-medium"
                    :title="task.title"
                >
                    {{ task.title }}
                </p>
                <div
                    class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        class="h-6 w-6 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                        @click.stop="emit('edit', task)"
                        title="Edit task"
                    >
                        <Pencil class="size-3" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        class="h-6 w-6 text-destructive hover:bg-destructive/10 hover:text-destructive"
                        @click.stop="showDeleteDialog = true"
                        title="Delete task"
                    >
                        <Trash2 class="size-3" />
                    </Button>
                </div>
            </div>

            <!-- Description Preview -->
            <div
                v-if="task.description"
                class="max-h-16 min-w-0 overflow-hidden text-[12px] leading-relaxed text-muted-foreground"
            >
                <div
                    class="task-description-preview"
                    v-html="task.description"
                ></div>
            </div>

            <!-- Footer: Meta tags & Avatar -->
            <div class="mt-1 flex items-end justify-between gap-2">
                <!-- Tags -->
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        v-if="
                            task.days_in_column !== null &&
                            task.days_in_column !== undefined
                        "
                        variant="secondary"
                        class="flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-medium"
                        title="Time in column"
                    >
                        <ClockAlert class="size-3 text-muted-foreground" />
                        <span>{{
                            task.days_in_column === 0
                                ? 'Today'
                                : `${task.days_in_column}d`
                        }}</span>
                    </Badge>

                    <Badge
                        v-if="task.due_date"
                        :variant="isOverdue(task) ? 'destructive' : 'secondary'"
                        class="flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-medium"
                        title="Due date"
                    >
                        <Calendar
                            class="size-3"
                            :class="!isOverdue(task) && 'text-muted-foreground'"
                        />
                        <span>{{
                            new Date(task.due_date).toLocaleDateString(
                                'en-US',
                                { day: 'numeric', month: 'short' },
                            )
                        }}</span>
                    </Badge>
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
</style>
