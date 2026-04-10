<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';

const isOpen = defineModel<boolean>('open', { default: false });

const form = useForm({
    name: '',
});

watch(isOpen, (open) => {
    if (open) {
        form.reset();
        form.clearErrors();
    }
});

const submit = () => {
    form.post('/columns', {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
            toast.success('Column created securely.');
            form.reset();
        },
    });
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[425px]" @open-auto-focus.prevent>
            <DialogHeader>
                <DialogTitle>Create new column</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submit">
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="column-name">Column Name</Label>
                        <Input
                            id="column-name"
                            type="text"
                            placeholder="e.g. Backlog, Review..."
                            v-model="form.name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        Create Column
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
