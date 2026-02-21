<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/tasks';
import type { TaskForm } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const isOpen = ref(false);

const form = useForm<TaskForm>({
    title: '',
    due_date: '',
});

const submit = () => {
    if (form.isDirty) {
        form.submit(store(), {
            onSuccess: () => {
                form.reset();
                isOpen.value = false;
                toast.success('Task created');
            },
        });
    }
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <Button>
                <Plus class="size-4" />
                New azzzz
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Create a new task</DialogTitle>
                <DialogDescription>
                    Add a task to your team's list
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid gap-2">
                    <Label for="task-title">Title</Label>
                    <Input
                        id="task-title"
                        type="text"
                        placeholder="Enter task title..."
                        v-model="form.title"
                    />
                    <InputError :message="form.errors.title" />
                </div>
                <div class="grid gap-2">
                    <Label for="task-due-date">Due date</Label>
                    <Input
                        id="task-due-date"
                        type="datetime-local"
                        v-model="form.due_date"
                    />
                    <InputError :message="form.errors.due_date" />
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
                        <Plus v-else class="size-4" />
                        Add task
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
