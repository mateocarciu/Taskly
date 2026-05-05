<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { DashboardColumnStat } from '@/types/index';
import { computed } from 'vue';

const props = defineProps<{
    columns: DashboardColumnStat[];
}>();

const totalColumnTasks = computed(() =>
    props.columns.reduce((sum, column) => sum + column.count, 0),
);

const sortedColumns = computed(() =>
    [...props.columns].sort((left, right) => right.count - left.count),
);

const columnPercent = (count: number): number => {
    if (totalColumnTasks.value === 0) return 0;
    return Math.round((count / totalColumnTasks.value) * 100);
};

const columnBarWidth = (count: number): string => {
    if (totalColumnTasks.value === 0) return '0%';

    const percent = (count / totalColumnTasks.value) * 100;
    return `${Math.max(percent, 8)}%`;
};
</script>

<template>
    <Card class="w-full min-w-0">
        <CardHeader class="pb-2">
            <div
                class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between sm:gap-3"
            >
                <div class="min-w-0 flex-1">
                    <CardTitle class="text-base">By Column</CardTitle>
                    <p class="text-xs text-muted-foreground">
                        Distribution of tasks across the workflow.
                    </p>
                </div>
                <p class="shrink-0 text-xs text-muted-foreground">
                    {{ totalColumnTasks }} tasks
                </p>
            </div>
        </CardHeader>
        <CardContent class="space-y-3">
            <div
                v-for="column in sortedColumns"
                :key="column.id"
                class="space-y-2 rounded-xl border bg-muted/20 p-3"
            >
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">
                            {{ column.name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ columnPercent(column.count) }}% of tasks
                        </p>
                    </div>
                    <p class="shrink-0 text-sm font-semibold">
                        {{ column.count }}
                    </p>
                </div>

                <div class="h-2 rounded-full bg-muted">
                    <div
                        class="h-2 rounded-full bg-primary transition-all"
                        :style="{ width: columnBarWidth(column.count) }"
                    />
                </div>
            </div>

            <p v-if="!columns.length" class="text-sm text-muted-foreground">
                No columns available.
            </p>
        </CardContent>
    </Card>
</template>
