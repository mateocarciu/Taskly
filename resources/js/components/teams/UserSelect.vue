<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ChevronsUpDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

defineProps<{
    users: User[];
    disabled?: boolean;
}>();

const emit = defineEmits<{
    select: [user: User];
}>();

const selected = ref<User | null>(null);

const label = computed(() =>
    selected.value ? selected.value.name : 'Select a user to add…',
);

const pick = (user: User) => {
    selected.value = user;
    emit('select', user);
};

defineExpose({ reset: () => { selected.value = null; } });
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="outline"
                class="w-full justify-between"
                :disabled="disabled || users.length === 0"
            >
                <span class="truncate text-sm" :class="selected ? '' : 'text-muted-foreground'">
                    {{ label }}
                </span>
                <ChevronsUpDown class="ml-2 size-4 shrink-0 opacity-50" />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            align="start"
            class="w-[--reka-dropdown-menu-trigger-width]"
        >
            <DropdownMenuLabel class="text-xs text-muted-foreground">Users</DropdownMenuLabel>
            <DropdownMenuItem
                v-for="user in users"
                :key="user.id"
                class="cursor-pointer gap-2 p-2"
                @click="pick(user)"
            >
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">{{ user.name }}</p>
                    <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                </div>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
