<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TaskAssigneeSelect from '@/components/tasks/TaskAssigneeSelect.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/tasks';
import type { Task, TeamMember } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    task: Task | null;
    teamMembers: TeamMember[];
}>();

const isOpen = defineModel<boolean>('open', { default: false });

const form = useForm({
    title: '',
    description: '',
    due_date: '',
    assigned_to: null as number | null,
});

const submit = () => {
    if (!props.task) return;

    form.put(update(props.task.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
            toast.success('Task updated');
        },
    });
};

watch([() => props.task, isOpen], ([task, open]) => {
    if (task && open) {
        form.title = task.title;
        form.description = task.description || '';
        form.due_date = task.due_date
            ? new Date(task.due_date).toISOString().slice(0, 16)
            : '';
        form.assigned_to = task.assigned_to ?? null;
        form.clearErrors();
    }
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Task details</DialogTitle>
                <DialogDescription> Update the task details </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid gap-2">
                    <Label for="edit-task-title">Title</Label>
                    <Input
                        id="edit-task-title"
                        type="text"
                        placeholder="Enter task title..."
                        v-model="form.title"
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-task-description">Description</Label>
                    <textarea
                        id="edit-task-description"
                        class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Add more details..."
                        v-model="form.description"
                    ></textarea>
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-task-due-date">Due date</Label>
                    <Input
                        id="edit-task-due-date"
                        type="datetime-local"
                        v-model="form.due_date"
                    />
                    <InputError :message="form.errors.due_date" />
                </div>

                <div class="grid gap-2">
                    <Label>Assigned to</Label>
                    <TaskAssigneeSelect
                        v-model="form.assigned_to"
                        :team-members="teamMembers"
                    />
                    <InputError :message="form.errors.assigned_to" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="!form.isDirty || form.processing"
                    >
                        <Spinner v-if="form.processing" />
                        <Save v-else class="size-4" />
                        Save changes
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
