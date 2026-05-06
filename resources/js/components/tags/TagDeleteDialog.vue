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
import type { Tag } from '@/types';

defineProps<{
    tag: Tag;
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
                <DialogTitle>Delete tag</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete the tag
                    <span class="font-medium">"{{ tag.name }}"</span>? Tasks
                    using this tag will no longer be labelled with it.
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
