<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, UserPlus } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    isOwner: boolean;
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'member',
});

watch(open, (val) => {
    if (val) {
        form.reset();
        form.clearErrors();
    }
});

const roleLabel = computed(() => (form.role === 'admin' ? 'Admin' : 'Member'));

const submit = () => {
    form.post('/settings/users', {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            toast.success('User created successfully.');
        },
    });
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg" @open-auto-focus.prevent>
            <DialogHeader>
                <DialogTitle>Create a new user</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="create-name">Name</Label>
                        <Input
                            id="create-name"
                            v-model="form.name"
                            type="text"
                            autocomplete="off"
                            required
                            :disabled="form.processing"
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="create-email">Email</Label>
                        <Input
                            id="create-email"
                            v-model="form.email"
                            type="email"
                            autocomplete="off"
                            required
                            :disabled="form.processing"
                        />
                        <InputError :message="form.errors.email" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="create-password">Password</Label>
                        <PasswordInput
                            id="create-password"
                            v-model="form.password"
                            autocomplete="new-password"
                            required
                            :disabled="form.processing"
                        />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="create-password-confirm"
                            >Confirm Password</Label
                        >
                        <PasswordInput
                            id="create-password-confirm"
                            v-model="form.password_confirmation"
                            autocomplete="new-password"
                            required
                            :disabled="form.processing"
                        />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="create-role">Role</Label>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                class="h-9 w-full justify-between px-3"
                                :disabled="form.processing"
                            >
                                <span class="truncate text-sm">
                                    {{ roleLabel }}
                                </span>
                                <ChevronsUpDown
                                    class="ml-2 size-4 shrink-0 opacity-50"
                                />
                            </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent
                            class="w-[--reka-dropdown-menu-trigger-width]"
                            align="start"
                        >
                            <DropdownMenuLabel
                                class="text-xs text-muted-foreground"
                            >
                                Role
                            </DropdownMenuLabel>
                            <DropdownMenuItem
                                class="cursor-pointer gap-2"
                                @click="form.role = 'member'"
                            >
                                <span>Member</span>
                                <Check
                                    v-if="form.role === 'member'"
                                    class="ml-auto size-4 text-primary"
                                />
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="props.isOwner"
                                class="cursor-pointer gap-2"
                                @click="form.role = 'admin'"
                            >
                                <span>Admin</span>
                                <Check
                                    v-if="form.role === 'admin'"
                                    class="ml-auto size-4 text-primary"
                                />
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <InputError :message="form.errors.role" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <UserPlus class="size-4" />
                        Create User
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
