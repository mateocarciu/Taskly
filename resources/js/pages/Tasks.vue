<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import TaskCreateDialog from '@/components/tasks/TaskCreateDialog.vue';
import TaskEditDialog from '@/components/tasks/TaskEditDialog.vue';
import TaskList from '@/components/tasks/TaskList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/tasks';
import type { BreadcrumbItem, PaginatedTasks, Task } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    tasks: PaginatedTasks;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tasks',
        href: index().url,
    },
];

const isEditModalOpen = ref(false);
const taskToEdit = ref<Task | null>(null);

const openEditModal = (task: Task) => {
    taskToEdit.value = task;
    isEditModalOpen.value = true;
};
</script>

<template>
    <Head title="Tasks" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Tasks</h1>
                    <p class="text-sm text-muted-foreground">
                        Manage your team's tasks
                    </p>
                </div>
                <TaskCreateDialog />
            </div>

            <div class="flex-1">
                <div class="mb-4 flex items-center">
                    <span
                        v-if="tasks.total > 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ tasks.from }}-{{ tasks.to }} of
                        {{ tasks.total }}
                    </span>
                </div>

                <TaskList :tasks="tasks.data" @edit="openEditModal" />

                <Pagination
                    :current-page="tasks.current_page"
                    :last-page="tasks.last_page"
                    :prev-page-url="tasks.prev_page_url"
                    :next-page-url="tasks.next_page_url"
                    :links="tasks.links"
                />
            </div>
        </div>

        <TaskEditDialog v-model:open="isEditModalOpen" :task="taskToEdit" />
    </AppLayout>
</template>
