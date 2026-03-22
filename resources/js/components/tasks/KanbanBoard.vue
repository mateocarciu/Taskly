<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { Column, Task } from '@/types';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import KanbanColumn from './KanbanColumn.vue';
import ColumnCreateDialog from './ColumnCreateDialog.vue';

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

const isCreateColumnOpen = ref(false);
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

        <div class="mt-0 shrink-0">
            <Button
                variant="outline"
                class="shadow-sm border-dashed text-muted-foreground hover:text-foreground hover:border-foreground transition-all"
                @click="isCreateColumnOpen = true"
            >
                <Plus class="mr-2 size-4" />
                Add Column
            </Button>
        </div>

        <ColumnCreateDialog v-model:open="isCreateColumnOpen" />
    </div>
</template>
