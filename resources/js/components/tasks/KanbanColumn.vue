<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { Column, Task } from '@/types';
import { router } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    GripVertical,
    MoreHorizontal,
    Pencil,
    Trash2,
    X,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import draggable from 'vuedraggable';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import TaskItem from './TaskItem.vue';

const props = defineProps<{
    column: Column;
    filters: Record<string, any>;
}>();

const emit = defineEmits<{
    edit: [task: Task];
}>();

const isEditingColumn = ref(false);
const editName = ref(props.column.name);

const localTasks = ref<Task[]>([...(props.column.tasks || [])]);
const pagination = ref<Column['pagination']>(
    props.column.pagination ? { ...props.column.pagination } : undefined,
);
const isLoadingMore = ref(false);
const isDone = computed(() => props.column.type === 'done');

const loadMoreTasks = async () => {
    if (!pagination.value || !pagination.value.has_more) return;

    isLoadingMore.value = true;
    try {
        const nextPage = pagination.value.current_page + 1;
        const params = new URLSearchParams();
        params.append('page', nextPage.toString());

        if (props.filters.search) {
            params.append('search', props.filters.search);
        }
        if (props.filters.assignee_id) {
            params.append('assignee_id', props.filters.assignee_id.toString());
        }
        if (props.filters.tag_ids) {
            const tagIds = Array.isArray(props.filters.tag_ids)
                ? props.filters.tag_ids
                : [props.filters.tag_ids];
            tagIds.forEach((id: any) => {
                params.append('tag_ids[]', id.toString());
            });
        }
        if (props.filters.due_date) {
            params.append('due_date', props.filters.due_date);
        }

        const response = await fetch(
            `/columns/${props.column.id}/tasks?${params.toString()}`,
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


watch(
    () => props.column.tasks,
    (newTasks) => {
        localTasks.value = [...(newTasks || [])];
        if (props.column.pagination) {
            pagination.value = { ...props.column.pagination };
        }
    },
);
</script>

<template>
    <div
        class="flex w-[350px] shrink-0 flex-col rounded-xl bg-[color-mix(in_srgb,var(--muted)_40%,var(--background))]"
    >
        <div class="sticky top-0 z-10 flex items-center justify-between px-4 pt-3 pb-3 bg-[color-mix(in_srgb,var(--muted)_40%,var(--background))] rounded-t-xl">
            <div v-if="isEditingColumn" class="flex flex-1 items-center gap-2">
                <input
                    v-model="editName"
                    class="flex h-8 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                    @keyup.enter="saveColumnName"
                    @blur="saveColumnName"
                    autoFocus
                />
                <Button
                    variant="ghost"
                    size="icon-sm"
                    class="h-8 w-8 text-muted-foreground hover:bg-primary/10"
                    @click="isEditingColumn = false"
                >
                    <X class="size-3" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    class="h-8 w-8 text-green-600 hover:bg-green-100 dark:text-green-400 dark:hover:bg-green-900/20"
                    @click="saveColumnName"
                >
                    <Check class="size-3" />
                </Button>
            </div>
            <h3
                v-else
                class="flex items-center font-semibold"
                @dblclick="isEditingColumn = true"
            >
                <button
                    type="button"
                    class="column-drag-handle mr-1 rounded p-0.5 text-muted-foreground hover:cursor-grab hover:bg-accent hover:text-foreground active:cursor-grabbing"
                    title="Drag to reorder column"
                >
                    <GripVertical class="size-3" />
                </button>
                {{ column.name }}
                <span
                    class="ml-2 min-w-[20px] rounded-full bg-secondary px-1.5 py-0.5 text-center text-xs font-medium text-secondary-foreground"
                >
                    {{ column.pagination?.total }}
                </span>
                <Check
                    v-if="isDone"
                    class="ml-2 size-3.5 shrink-0 text-green-500 dark:text-green-400"
                    title="Work items moved to this column are marked as done"
                />
            </h3>

            <div v-if="!isEditingColumn">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="h-6 w-6 text-muted-foreground"
                        >
                            <MoreHorizontal class="size-3.5" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-32">
                        <DropdownMenuItem @click="isEditingColumn = true">
                            <Pencil class="mr-2 size-3" />
                            <span>Rename</span>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            @click="isDeleteColumnOpen = true"
                            class="text-destructive focus:text-destructive focus:bg-destructive/10"
                        >
                            <Trash2 class="mr-2 size-3" />
                            <span>Delete</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <div class="no-scrollbar flex flex-1 flex-col px-3 pb-3">
            <draggable
                v-model="localTasks"
                group="tasks"
                item-key="id"
                class="flex flex-1 flex-col gap-3 pb-2 min-h-[100px]"
                ghost-class="opacity-50"
                @change="onDragChange"
            >
                <template #item="{ element }">
                    <div class="cursor-grab active:cursor-grabbing">
                        <TaskItem
                            :task="element"
                            :is-done="props.column.type === 'done'"
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

        <ConfirmDeleteDialog
            v-model:open="isDeleteColumnOpen"
            title="Delete column"
            :description="`Are you sure you want to delete the &ldquo;${column.name}&rdquo; column? This action cannot be undone.`"
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
