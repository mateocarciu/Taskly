<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TaskAssigneeSelect from '@/components/tasks/TaskAssigneeSelect.vue';
import TaskRichTextEditor from '@/components/tasks/TaskRichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { TeamMember } from '@/types';
import { TaskEditFormState } from '@/types';
import { Save } from 'lucide-vue-next';

defineProps<{
    form: TaskEditFormState;
    teamMembers: TeamMember[];
    isLoadingDetails: boolean;
}>();

defineEmits<{
    submit: [];
    cancel: [];
    'update:title': [value: string];
    'update:description': [value: string];
    'update:due-date': [value: string];
    'update:assigned-to': [value: number | null];
}>();
</script>

<template>
    <div class="p-6 xl:pr-8">
        <form class="space-y-4" @submit.prevent="$emit('submit')">
            <div class="grid gap-2">
                <Label for="edit-task-title">Title</Label>
                <Input
                    id="edit-task-title"
                    type="text"
                    placeholder="Enter task title..."
                    :model-value="form.title"
                    @update:model-value="$emit('update:title', String($event))"
                />
                <InputError :message="form.errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="edit-task-description">Description</Label>
                <TaskRichTextEditor
                    :model-value="form.description"
                    placeholder="Add more details..."
                    min-height="20rem"
                    @update:model-value="$emit('update:description', $event)"
                />
                <InputError :message="form.errors.description" />
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="edit-task-due-date">Due date</Label>
                    <Input
                        id="edit-task-due-date"
                        type="datetime-local"
                        :model-value="form.due_date"
                        @update:model-value="
                            $emit('update:due-date', String($event))
                        "
                    />
                    <InputError :message="form.errors.due_date" />
                </div>

                <div class="grid gap-2">
                    <Label>Assigned to</Label>
                    <TaskAssigneeSelect
                        :model-value="form.assigned_to"
                        :team-members="teamMembers"
                        @update:model-value="
                            $emit('update:assigned-to', $event)
                        "
                    />
                    <InputError :message="form.errors.assigned_to" />
                </div>
            </div>

            <DialogFooter class="pt-2">
                <Button
                    type="button"
                    variant="outline"
                    @click="$emit('cancel')"
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    :disabled="
                        !form.isDirty || form.processing || isLoadingDetails
                    "
                >
                    <Spinner v-if="form.processing" />
                    <Save v-else class="size-4" />
                    Save changes
                </Button>
            </DialogFooter>
        </form>
    </div>
</template>
