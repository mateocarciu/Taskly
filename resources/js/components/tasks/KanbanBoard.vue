<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { Column, Task } from '@/types';
import { router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import KanbanColumn from './KanbanColumn.vue';

const props = defineProps<{
    columns: Column[];
}>();

const emit = defineEmits<{
    edit: [task: Task];
}>();

const localColumns = ref<Column[]>([]);

watch(
    () => props.columns,
    (newCols) => {
        localColumns.value = JSON.parse(JSON.stringify(newCols));
    },
    { immediate: true, deep: true },
);

const createColumn = () => {
    const name = prompt('Enter column name:');
    if (name) {
        router.post(
            '/columns',
            { name },
            {
                preserveScroll: true,
                onSuccess: () => toast.success('Column created'),
            },
        );
    }
};
</script>

<template>
    <div
        class="relative flex h-full w-full items-start gap-4 overflow-x-auto pb-4"
    >
        <KanbanColumn
            v-for="column in localColumns"
            :key="column.id"
            :column="column"
            @edit="emit('edit', $event)"
        />

        <div class="w-[350px] shrink-0 pr-4">
            <Button
                variant="outline"
                class="h-12 w-full border-dashed bg-background/50 hover:bg-background/80"
                @click="createColumn"
            >
                <Plus class="mr-2 size-4" />
                Add Column
            </Button>
        </div>
    </div>
</template>
