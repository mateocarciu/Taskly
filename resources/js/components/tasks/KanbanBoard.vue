<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { Column, Task } from '@/types';
import { router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import draggable from 'vuedraggable';
import ColumnCreateDialog from './ColumnCreateDialog.vue';
import KanbanColumn from './KanbanColumn.vue';

const props = defineProps<{
    columns: Column[];
}>();

const emit = defineEmits<{
    edit: [task: Task];
}>();

const localColumns = ref<Column[]>([]);
const previousColumns = ref<Column[]>([]);

const cloneColumns = (columns: Column[]): Column[] => {
    return columns.map((column) => ({
        ...column,
        tasks: [...(column.tasks ?? [])],
        pagination: column.pagination ? { ...column.pagination } : undefined,
    }));
};

watch(
    () => props.columns,
    (newCols) => {
        localColumns.value = cloneColumns(newCols);
    },
    { immediate: true, deep: true },
);

const isCreateColumnOpen = ref(false);

const onColumnDragStart = () => {
    previousColumns.value = cloneColumns(localColumns.value);
};

const onColumnDragChange = (event: {
    moved?: { newIndex: number; oldIndex: number; element: Column };
}) => {
    if (!event.moved) return;

    const movedColumn = event.moved.element;

    router.put(
        `/columns/${movedColumn.id}/sequence`,
        {
            order: event.moved.newIndex,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Columns reordered');
            },
            onError: () => {
                localColumns.value = cloneColumns(previousColumns.value);
                toast.error('Unable to reorder columns');
            },
        },
    );
};
</script>

<template>
    <div
        class="relative flex h-full w-full items-start gap-4 overflow-x-auto pb-4"
    >
        <draggable
            v-model="localColumns"
            item-key="id"
            class="flex h-full w-full items-start gap-4"
            handle=".column-drag-handle"
            ghost-class="opacity-60"
            @start="onColumnDragStart"
            @change="onColumnDragChange"
        >
            <template #item="{ element }">
                <KanbanColumn :column="element" @edit="emit('edit', $event)" />
            </template>

            <template #footer>
                <div class="mt-0 shrink-0">
                    <Button
                        variant="outline"
                        class="border-dashed text-muted-foreground shadow-sm transition-all hover:border-foreground hover:text-foreground"
                        @click="isCreateColumnOpen = true"
                    >
                        <Plus class="mr-2 size-4" />
                        Add Column
                    </Button>
                </div>
            </template>
        </draggable>

        <ColumnCreateDialog v-model:open="isCreateColumnOpen" />
    </div>
</template>
