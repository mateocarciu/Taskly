<script setup lang="ts">
import TaskItem from '@/components/tasks/TaskItem.vue';
import type { Task } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps<{
    tasks: Task[];
}>();

const emit = defineEmits<{
    edit: [task: Task];
}>();

const page = usePage();

watch(
    () => props.tasks,
    (tasks) => {
        if (tasks.length === 0) {
            router.get(
                `/tasks?page=${Math.max(1, parseInt(((page.props as any).query?.page as string) || '1') - 1)}`,
            );
        }
    },
);
</script>

<template>
    <div
        v-if="tasks.length === 0"
        class="rounded-xl border border-dashed p-8 text-center"
    >
        <p class="text-muted-foreground">No tasks !</p>
    </div>

    <div v-else class="space-y-3">
        <TaskItem
            v-for="task in tasks"
            :key="task.id"
            :task="task"
            @edit="emit('edit', $event)"
        />
    </div>
</template>
