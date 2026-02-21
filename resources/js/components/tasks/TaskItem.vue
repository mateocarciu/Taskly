<script setup lang="ts">
import TaskDeleteDialog from '@/components/tasks/TaskDeleteDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { destroy, update } from '@/routes/tasks';
import type { Task } from '@/types';
import { router } from '@inertiajs/vue3';
import {
    Calendar,
    CheckCircle,
    Circle,
    ClockAlert,
    Loader,
    Pencil,
    Trash2,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    task: Task;
}>();

const emit = defineEmits<{
    edit: [task: Task];
}>();

const showDeleteDialog = ref(false);

const isOverdue = (task: Task) => {
    return new Date(task.due_date) < new Date() && !task.completed;
};

const toggleCompleted = () => {
    router.put(
        update(props.task.id).url,
        { completed: !props.task.completed },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    props.task.completed
                        ? 'Task marked as complete'
                        : 'Task marked as incomplete',
                );
            },
        },
    );
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
        :class="[
            'transition-all hover:shadow-md',
            task.completed && 'opacity-60',
        ]"
    >
        <CardContent class="flex items-center gap-4 p-4">
            <button
                @click="toggleCompleted"
                class="text-muted-foreground transition-colors hover:text-primary"
                :title="
                    task.completed ? 'Mark as incomplete' : 'Mark as completed'
                "
            >
                <CheckCircle
                    v-if="task.completed"
                    class="size-5 text-green-500"
                />
                <Circle v-else class="size-5" />
            </button>

            <div class="min-w-0 flex-1">
                <p
                    :class="[
                        'truncate font-medium',
                        task.completed && 'text-muted-foreground line-through',
                    ]"
                >
                    tot
                    {{ task.title }}
                </p>
                <div
                    class="mt-1 flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Calendar class="size-3.5" />
                    <span
                        :class="
                            isOverdue(task) && 'font-medium text-destructive'
                        "
                    >
                        {{
                            new Date(task.due_date).toLocaleDateString('fr-FR')
                        }}
                    </span>
                </div>
                <span v-if="task.creator" class="flex text-xs">
                    Created by
                    <p class="ml-1 font-semibold">{{ task.creator.name }}</p>
                </span>
            </div>

            <Badge v-if="task.completed" class="gap-1 bg-green-500">
                <CheckCircle class="size-3" />
                Done
            </Badge>
            <Badge
                v-else-if="isOverdue(task)"
                variant="destructive"
                class="gap-1"
            >
                <ClockAlert class="size-3" />
                Overdue
            </Badge>
            <Badge v-else class="gap-1 bg-amber-500">
                <Loader class="size-3" />
                Pending
            </Badge>

            <div class="flex items-center gap-1">
                <Button
                    variant="ghost"
                    size="icon-sm"
                    @click="emit('edit', task)"
                    title="Edit task"
                >
                    <Pencil class="size-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    @click="showDeleteDialog = true"
                    class="text-destructive hover:text-destructive"
                    title="Delete task"
                >
                    <Trash2 class="size-4" />
                </Button>
            </div>
        </CardContent>
    </Card>

    <TaskDeleteDialog
        v-model:open="showDeleteDialog"
        :task="task"
        @confirm="deleteTask"
    />
</template>
