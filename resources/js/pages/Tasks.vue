<script setup lang="ts">
import KanbanBoard from '@/components/tasks/KanbanBoard.vue';
import TaskCreateDialog from '@/components/tasks/TaskCreateDialog.vue';
import TaskEditDialog from '@/components/tasks/TaskEditDialog.vue';
import TaskFilters from '@/components/tasks/TaskFilters.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/tasks';
import type { BreadcrumbItem, Column, Tag, Task, TeamMember } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    columns: Column[];
    teamMembers: TeamMember[];
    tags: Tag[];
    filters?: {
        search?: string;
        assignee_id?: number | 'unassigned' | '';
        tag_ids?: number[];
        due_date?: string;
    };
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

const handleFilterChange = (newFilters: any) => {
    const query: Record<string, any> = {};

    if (newFilters.search) query.search = newFilters.search;
    if (newFilters.assignee_id) query.assignee_id = newFilters.assignee_id;
    if (newFilters.tag_ids && newFilters.tag_ids.length > 0) {
        query.tag_ids = newFilters.tag_ids;
    }
    if (newFilters.due_date) query.due_date = newFilters.due_date;

    router.get(index().url, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
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
                <TaskCreateDialog
                    :team-members="teamMembers"
                    :available-tags="tags"
                />
            </div>

            <TaskFilters
                :filters="filters || {}"
                :team-members="teamMembers"
                :tags="tags"
                @change="handleFilterChange"
            />

            <div
                class="h-[calc(100vh-[breadcrumbs height]-header)] flex-1 overflow-hidden"
            >
                <KanbanBoard
                    :columns="columns"
                    :filters="filters || {}"
                    @edit="openTaskDetails"
                />
            </div>
        </div>

        <TaskEditDialog
            v-model:open="isEditModalOpen"
            :task="taskToEdit"
            :team-members="teamMembers"
            :available-tags="tags"
        />
    </AppLayout>
</template>
