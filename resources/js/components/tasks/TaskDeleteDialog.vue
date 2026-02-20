<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Task } from '@/types';

defineProps<{
    task: Task;
}>();

const isOpen = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    confirm: [];
}>();
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete task</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete "{{ task.title }}"? This
                    action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="isOpen = false">
                    Cancel
                </Button>
                <Button variant="destructive" @click="emit('confirm')">
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
