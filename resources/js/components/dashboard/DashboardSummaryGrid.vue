<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import type { DashboardStats } from '@/types/index';
import { AlertTriangle, CalendarDays, ListCheck, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    stats: DashboardStats;
}>();

const summaryCards = computed(() => [
    {
        title: 'Team members',
        value: props.stats.team_members,
        description: 'People in this team',
        icon: Users,
        tone: 'text-slate-600 bg-slate-500/10',
    },
    {
        title: 'Total tasks',
        value: props.stats.total_tasks,
        description: 'All tasks in the team',
        icon: ListCheck,
        tone: 'text-blue-600 bg-blue-500/10',
    },
    {
        title: 'Overdue',
        value: props.stats.overdue_tasks,
        description: 'Past their due date',
        icon: AlertTriangle,
        tone: 'text-rose-600 bg-rose-500/10',
    },
    {
        title: 'Due today',
        value: props.stats.due_today_tasks,
        description: 'Need attention before end of day',
        icon: CalendarDays,
        tone: 'text-amber-600 bg-amber-500/10',
    },
]);
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Card
            v-for="card in summaryCards"
            :key="card.title"
            class="border-border/80"
        >
            <CardContent class="flex items-start justify-between gap-3 p-4">
                <div>
                    <p
                        class="text-xs font-medium text-muted-foreground uppercase"
                    >
                        {{ card.title }}
                    </p>
                    <p class="mt-1 text-2xl font-bold">
                        {{ card.value }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ card.description }}
                    </p>
                </div>
                <div class="rounded-full p-2" :class="card.tone">
                    <component :is="card.icon" class="size-4" />
                </div>
            </CardContent>
        </Card>
    </div>
</template>
