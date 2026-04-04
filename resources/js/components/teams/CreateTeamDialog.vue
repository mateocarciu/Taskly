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
    form.post('/settings/teams', {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
            toast.success('Team created successfully.');
            form.reset();
        },
    });
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Create a new team</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submit">
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="team-name">Team Name</Label>
                        <Input
                            id="team-name"
                            type="text"
                            placeholder="e.g. Product Team"
                            v-model="form.name"
                            autofocus
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
                        Create Team
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
