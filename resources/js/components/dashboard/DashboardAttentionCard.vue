<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate } from '@/composables/useDateFormatter';
import type { DashboardTaskStat } from '@/types/index';
import { ChevronDown } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    tasks: DashboardTaskStat[];
}>();

const attentionScrollEl = ref<HTMLElement | null>(null);
const showAttentionScrollHint = ref(false);

const updateAttentionScrollHint = (): void => {
    const element = attentionScrollEl.value;

    if (!element) {
        showAttentionScrollHint.value = false;
        return;
    }

    const hasOverflow = element.scrollHeight > element.clientHeight + 4;
    showAttentionScrollHint.value =
        hasOverflow &&
        element.scrollTop + element.clientHeight < element.scrollHeight - 4;
};

watch(
    () => props.tasks.length,
    async () => {
        await nextTick();
        updateAttentionScrollHint();
    },
    { immediate: true },
);

onMounted(() => {
    updateAttentionScrollHint();
});

onBeforeUnmount(() => {
    showAttentionScrollHint.value = false;
});
</script>

<template>
    <Card>
        <CardHeader class="pb-2">
            <CardTitle class="text-base">To Handle Now</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
            <div
                ref="attentionScrollEl"
                class="relative max-h-96 space-y-2 overflow-y-auto"
                @scroll="updateAttentionScrollHint"
            >
                <div class="space-y-2 px-4 pt-4">
                    <div
                        v-for="task in tasks"
                        :key="task.id"
                        class="flex items-center justify-between gap-3 rounded-lg border p-3"
                    >
                        <div class="min-w-0 space-y-1">
                            <p class="truncate text-sm font-medium">
                                {{ task.title }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ task.column_name }}
                                <span v-if="task.due_date">
                                    · due {{ formatDate(task.due_date) }}
                                </span>
                            </p>
                        </div>
                        <span
                            class="shrink-0 rounded-full bg-destructive/10 px-2 py-1 text-xs font-medium text-destructive"
                        >
                            {{ task.reason }}
                        </span>
                    </div>
                </div>

                <p
                    v-if="!tasks.length"
                    class="px-4 py-3 text-sm text-muted-foreground"
                >
                    No urgent task right now.
                </p>

                <div
                    v-if="showAttentionScrollHint"
                    class="pointer-events-none sticky bottom-0 flex justify-center bg-linear-to-b from-transparent to-background/80 px-4 py-3"
                >
                    <div
                        class="flex items-center gap-1 rounded-full border bg-background/95 px-3 py-2 text-xs font-medium text-muted-foreground shadow-lg backdrop-blur"
                    >
                        <ChevronDown class="size-3.5" />
                        <span>More below</span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
