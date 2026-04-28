<script setup lang="ts">
import DashboardAttentionCard from '@/components/dashboard/DashboardAttentionCard.vue';
import DashboardColumnBreakdown from '@/components/dashboard/DashboardColumnBreakdown.vue';
import DashboardRecentActivity from '@/components/dashboard/DashboardRecentActivity.vue';
import DashboardSummaryGrid from '@/components/dashboard/DashboardSummaryGrid.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { DashboardStats } from '@/types/index';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    stats: DashboardStats;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-5 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">
                        {{ props.stats.team_name }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Daily overview of workload, urgency and recent updates.
                    </p>
                </div>
            </div>

            <section>
                <DashboardSummaryGrid :stats="props.stats" />
            </section>

            <section class="grid gap-4 xl:grid-cols-[1.5fr_1fr]">
                <DashboardAttentionCard :tasks="props.stats.attention_tasks" />
                <DashboardColumnBreakdown :columns="props.stats.column_stats" />
            </section>

            <section>
                <DashboardRecentActivity :tasks="props.stats.recent_tasks" />
            </section>
        </div>
    </AppLayout>
</template>
