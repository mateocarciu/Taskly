<script setup lang="ts">
import { ref, watch } from 'vue';
import draggable from 'vuedraggable';
import type { Column, Task } from '@/types';
import TaskItem from './TaskItem.vue';
import ColumnDeleteDialog from './ColumnDeleteDialog.vue';
import { Button } from '@/components/ui/button';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

const props = defineProps<{
    column: Column;
}>();

const emit = defineEmits<{
    edit: [task: Task];
}>();

const isEditingColumn = ref(false);
const editName = ref(props.column.name);

const localTasks = ref(props.column.tasks);

watch(() => props.column.tasks, (newTasks) => {
    localTasks.value = newTasks;
}, { deep: true });

const saveColumnName = () => {
    if (editName.value.trim() && editName.value !== props.column.name) {
        router.put(`/columns/${props.column.id}`, { name: editName.value }, {
            preserveScroll: true,
            onSuccess: () => {
                isEditingColumn.value = false;
                toast.success('Column updated');
            }
        });
    } else {
        isEditingColumn.value = false;
    }
};

const isDeleteColumnOpen = ref(false);

const deleteColumn = () => {
    isDeleteColumnOpen.value = false;
    router.delete(`/columns/${props.column.id}`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Column deleted'),
        onError: (err) => {
            if (err.column) toast.error(err.column);
            else toast.error('Failed to delete column');
        }
    });
};

const onDragChange = (event: any) => {
    if (event.added || event.moved) {
        const item = event.added ? event.added.element : event.moved.element;
        const newIndex = event.added ? event.added.newIndex : event.moved.newIndex;
        
        router.put(`/tasks/${item.id}/sequence`, {
            column_id: props.column.id,
            order: newIndex,
        }, { preserveScroll: true });
    }
};
</script>

<template>
    <div class="flex w-[350px] shrink-0 flex-col rounded-xl bg-muted/40 p-3 h-full max-h-[calc(100vh-12rem)]">
        <div class="mb-3 flex items-center justify-between px-1">
            <div v-if="isEditingColumn" class="flex flex-1 items-center gap-2">
                <input 
                    v-model="editName" 
                    class="flex h-8 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    @keyup.enter="saveColumnName"
                    @blur="saveColumnName"
                    autoFocus
                />
            </div>
            <h3 v-else class="font-semibold flex items-center" @dblclick="isEditingColumn = true">
                {{ column.name }}
                <span class="ml-2 rounded-full bg-secondary min-w-[20px] px-1.5 py-0.5 text-center text-xs font-medium text-secondary-foreground">
                    {{ column.tasks.length }}
                </span>
            </h3>
            
            <div class="flex items-center space-x-1" v-if="!isEditingColumn">
                <Button variant="ghost" size="icon-sm" class="h-6 w-6 text-muted-foreground" @click="isEditingColumn = true">
                    <Pencil class="size-3" />
                </Button>
                <Button variant="ghost" size="icon-sm" class="h-6 w-6 text-destructive hover:bg-destructive/10 hover:text-destructive" @click="isDeleteColumnOpen = true">
                    <Trash2 class="size-3" />
                </Button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto min-h-[100px] no-scrollbar">
            <draggable
                v-model="localTasks"
                group="tasks"
                item-key="id"
                class="flex min-h-full flex-col gap-3 pb-2"
                ghost-class="opacity-50"
                @change="onDragChange"
            >
                <template #item="{ element }">
                    <div class="cursor-grab active:cursor-grabbing">
                        <TaskItem :task="element" @edit="emit('edit', $event)" />
                    </div>
                </template>
            </draggable>
        </div>

        <ColumnDeleteDialog 
            v-model:open="isDeleteColumnOpen" 
            :column="column"
            @confirm="deleteColumn" 
        />
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
