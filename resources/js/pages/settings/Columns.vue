<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem, Column } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Info } from '@lucide/vue';
import { toast } from 'vue-sonner';

defineProps<{
    columns: Column[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Columns & Statuses',
        href: '/settings/columns',
    },
];

const updateColumnType = (
    column: Column,
    newType: 'todo' | 'in_progress' | 'done',
) => {
    if (column.type === newType) return;

    router.put(
        `/columns/${column.id}`,
        {
            name: column.name,
            type: newType,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    `Column "${column.name}" category updated to "${newType === 'todo' ? 'To Do' : newType === 'done' ? 'Done' : 'In Progress'}"`,
                );
            },
            onError: () => {
                toast.error('Failed to update column category');
            },
        },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Columns & Statuses" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Columns & Statuses"
                    description="Map your board columns to status categories (To Do, In Progress, Done) to manage workflow metrics."
                />

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base"
                            >Status Category Mapping</CardTitle
                        >
                        <CardDescription>
                            Status categories determine how tasks in each column
                            are treated for reports, search queries, and
                            dashboard overdue alerts.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="divide-y divide-border">
                        <div
                            v-for="column in columns"
                            :key="column.id"
                            class="flex flex-col gap-4 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <span
                                    class="text-sm font-medium text-foreground"
                                    >{{ column.name }}</span
                                >
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Current Category:
                                    <span
                                        :class="[
                                            'ml-1.5 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase',
                                            {
                                                'border border-border bg-muted text-muted-foreground':
                                                    column.type === 'todo',
                                                'border border-blue-500/20 bg-blue-500/10 text-blue-500':
                                                    column.type ===
                                                    'in_progress',
                                                'border border-green-500/20 bg-green-500/10 text-green-500':
                                                    column.type === 'done',
                                            },
                                        ]"
                                    >
                                        {{
                                            column.type === 'todo'
                                                ? 'To Do'
                                                : column.type === 'done'
                                                  ? 'Done'
                                                  : 'In Progress'
                                        }}
                                    </span>
                                </p>
                            </div>

                            <div
                                class="flex w-fit items-center gap-1.5 rounded-lg border bg-muted/40 p-1.5"
                            >
                                <button
                                    @click="updateColumnType(column, 'todo')"
                                    :class="[
                                        'rounded-md px-3 py-1 text-xs font-medium transition-all',
                                        column.type === 'todo'
                                            ? 'bg-background text-foreground shadow'
                                            : 'text-muted-foreground hover:text-foreground',
                                    ]"
                                >
                                    To Do
                                </button>
                                <button
                                    @click="
                                        updateColumnType(column, 'in_progress')
                                    "
                                    :class="[
                                        'rounded-md px-3 py-1 text-xs font-medium transition-all',
                                        column.type === 'in_progress'
                                            ? 'border border-blue-500/20 bg-blue-500/10 font-semibold text-blue-600 shadow-xs dark:text-blue-400'
                                            : 'text-muted-foreground hover:text-foreground',
                                    ]"
                                >
                                    In Progress
                                </button>
                                <button
                                    @click="updateColumnType(column, 'done')"
                                    :class="[
                                        'rounded-md px-3 py-1 text-xs font-medium transition-all',
                                        column.type === 'done'
                                            ? 'border border-green-500/20 bg-green-500/10 font-semibold text-green-600 shadow-xs dark:text-green-400'
                                            : 'text-muted-foreground hover:text-foreground',
                                    ]"
                                >
                                    Done
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="!columns.length"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            No columns created yet.
                        </div>
                    </CardContent>
                </Card>

                <div
                    class="flex items-start gap-2.5 rounded-lg border bg-accent/40 p-4"
                >
                    <Info class="mt-0.5 size-4.5 shrink-0 text-blue-500" />
                    <p class="text-xs leading-relaxed text-muted-foreground">
                        <strong>Task Completion:</strong> Any task placed in a
                        column categorized as
                        <span
                            class="font-semibold text-green-600 dark:text-green-400"
                            >Done</span
                        >
                        will automatically be marked as completed. It will no
                        longer show up as overdue or require urgent attention on
                        your Dashboard.
                    </p>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
