<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { Column, Task } from '@/types';
import { router } from '@inertiajs/vue3';
import { ChevronDown, Pencil, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import draggable from 'vuedraggable';
import ColumnDeleteDialog from './ColumnDeleteDialog.vue';
import TaskItem from './TaskItem.vue';

const props = defineProps<{
    column: Column;
}>();

const emit = defineEmits<{
    edit: [task: Task];
}>();

const isEditingColumn = ref(false);
const editName = ref(props.column.name);

const localTasks = ref<Task[]>([...(props.column.tasks || [])]);
const pagination = ref<Column['pagination']>(
    props.column.pagination ? { ...props.column.pagination } : undefined
);
const isLoadingMore = ref(false);

watch(
    () => props.column.tasks,
    (newTasks) => {
        localTasks.value = [...(newTasks || [])];
        if (props.column.pagination) {
            pagination.value = { ...props.column.pagination };
        }
    }
);

const loadMoreTasks = async () => {
    if (!pagination.value || !pagination.value.has_more) return;

    isLoadingMore.value = true;
    try {
        const nextPage = pagination.value.current_page + 1;
        const response = await fetch(
            `/columns/${props.column.id}/tasks?page=${nextPage}`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        if (!response.ok) throw new Error('Network error');

        const { data, meta } = await response.json();

        localTasks.value.push(...data);

        pagination.value = {
            current_page: meta.current_page,
            last_page: meta.last_page,
            total: meta.total,
            has_more: meta.current_page < meta.last_page,
        };
    } catch {
        toast.error('Failed to load more tasks');
    } finally {
        isLoadingMore.value = false;
    }
};

const saveColumnName = () => {
    if (editName.value.trim() && editName.value !== props.column.name) {
        router.put(
            `/columns/${props.column.id}`,
            { name: editName.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    isEditingColumn.value = false;
                    toast.success('Column updated');
                },
            },
        );
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
        },
    });
};

const onDragChange = (event: any) => {
    if (event.added || event.moved) {
        const item = event.added ? event.added.element : event.moved.element;
        const newIndex = event.added
            ? event.added.newIndex
            : event.moved.newIndex;

        router.put(
            `/tasks/${item.id}/sequence`,
            {
                column_id: props.column.id,
                order: newIndex,
            },
            { preserveScroll: true },
        );
    }
};
</script>

<template>
    <div
        class="flex h-full max-h-[calc(100vh-12rem)] w-[350px] shrink-0 flex-col rounded-xl bg-muted/40 p-3"
    >
        <div class="mb-3 flex items-center justify-between px-1">
            <div v-if="isEditingColumn" class="flex flex-1 items-center gap-2">
                <input
                    v-model="editName"
                    class="flex h-8 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                    @keyup.enter="saveColumnName"
                    @blur="saveColumnName"
                    autoFocus
                />
            </div>
            <h3
                v-else
                class="flex items-center font-semibold"
                @dblclick="isEditingColumn = true"
            >
                {{ column.name }}
                <span
                    class="ml-2 min-w-[20px] rounded-full bg-secondary px-1.5 py-0.5 text-center text-xs font-medium text-secondary-foreground"
                >
                    {{ column.pagination?.total }}
                </span>
            </h3>

            <div class="flex items-center space-x-1" v-if="!isEditingColumn">
                <Button
                    variant="ghost"
                    size="icon-sm"
                    class="h-6 w-6 text-muted-foreground"
                    @click="isEditingColumn = true"
                >
                    <Pencil class="size-3" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    class="h-6 w-6 text-destructive hover:bg-destructive/10 hover:text-destructive"
                    @click="isDeleteColumnOpen = true"
                >
                    <Trash2 class="size-3" />
                </Button>
            </div>
        </div>

        <div class="no-scrollbar min-h-[100px] flex-1 overflow-y-auto">
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
                        <TaskItem
                            :task="element"
                            @edit="emit('edit', $event)"
                        />
                    </div>
                </template>
            </draggable>
            <div
                v-if="isLoadingMore"
                class="flex justify-center p-3 opacity-80"
            >
                <Spinner class="size-4 text-muted-foreground" />
            </div>
            <div
                v-else-if="pagination?.has_more"
                class="flex justify-center p-2 opacity-80 transition-opacity hover:opacity-100"
            >
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 w-full text-xs text-muted-foreground"
                    @click="loadMoreTasks"
                >
                    Load more tasks
                    <ChevronDown class="ml-1 size-3" />
                </Button>
            </div>
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
