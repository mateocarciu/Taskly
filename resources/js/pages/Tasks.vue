<script setup lang="ts">
import KanbanBoard from '@/components/tasks/KanbanBoard.vue';
import TaskCreateDialog from '@/components/tasks/TaskCreateDialog.vue';
import TaskEditDialog from '@/components/tasks/TaskEditDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/tasks';
import type { BreadcrumbItem, Column, Task, TeamMember } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    columns: Column[];
    teamMembers: TeamMember[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tasks',
        href: index().url,
    },
];

const isEditModalOpen = ref(false);
const taskToEdit = ref<Task | null>(null);

const openTaskDetails = (task: Task) => {
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
                <TaskCreateDialog :team-members="teamMembers" />
            </div>

            <div
                class="h-[calc(100vh-[breadcrumbs height]-header)] flex-1 overflow-hidden"
            >
                <KanbanBoard :columns="columns" @edit="openTaskDetails" />
            </div>
        </div>

        <TaskEditDialog
            v-model:open="isEditModalOpen"
            :task="taskToEdit"
            :team-members="teamMembers"
        />
    </AppLayout>
</template>
