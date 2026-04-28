<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate } from '@/composables/useDateFormatter';
import type { DashboardTaskStat } from '@/types/index';
import { Clock3 } from 'lucide-vue-next';

defineProps<{
    tasks: DashboardTaskStat[];
}>();
</script>

<template>
    <Card>
        <CardHeader class="pb-2">
            <CardTitle class="text-base">Recent Activity</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2">
            <div
                v-for="task in tasks"
                :key="task.id"
                class="grid gap-2 rounded-lg border p-3 md:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_auto] md:items-center"
            >
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium">
                        {{ task.title }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ task.column_name }}
                    </p>
                </div>

                <p class="text-xs text-muted-foreground">
                    <span v-if="task.assignee_name">
                        {{ task.assignee_name }}
                    </span>
                    <span v-else>Not assigned</span>
                    <span v-if="task.comments_count">
                        · {{ task.comments_count }} comment(s)
                    </span>
                </p>

                <div
                    class="flex items-center gap-1 text-xs text-muted-foreground md:justify-end"
                >
                    <Clock3 class="size-3.5" />
                    <span>{{ formatDate(task.due_date) }}</span>
                </div>
            </div>

            <p v-if="!tasks.length" class="text-sm text-muted-foreground">
                No tasks yet.
            </p>
        </CardContent>
    </Card>
</template>
