<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/composables/useInitials';
import type { TeamMember } from '@/types';
import { Check, ChevronsUpDown } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    modelValue: number | null;
    teamMembers: TeamMember[];
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | null): void;
}>();

const { getInitials } = useInitials();

const selectedMember = computed(
    () =>
        props.teamMembers.find((member) => member.id === props.modelValue) ??
        null,
);

const triggerLabel = computed(() => selectedMember.value?.name ?? 'Unassigned');

const selectMember = (memberId: number | null) => {
    emit('update:modelValue', memberId);
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                type="button"
                variant="outline"
                class="h-10 w-full justify-between px-3"
            >
                <div class="flex min-w-0 items-center gap-2">
                    <Avatar class="h-6 w-6 border border-border">
                        <AvatarFallback class="text-[10px] font-semibold">
                            {{
                                selectedMember
                                    ? getInitials(selectedMember.name)
                                    : '--'
                            }}
                        </AvatarFallback>
                    </Avatar>
                    <span class="truncate text-sm">{{ triggerLabel }}</span>
                </div>
                <ChevronsUpDown
                    class="ml-2 h-4 w-4 shrink-0 text-muted-foreground"
                />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            class="w-[--reka-dropdown-menu-trigger-width] min-w-64"
            align="start"
        >
            <DropdownMenuLabel>Assign task</DropdownMenuLabel>
            <DropdownMenuSeparator />

            <DropdownMenuItem
                class="cursor-pointer"
                @click="selectMember(null)"
            >
                <Avatar class="h-6 w-6 border border-border">
                    <AvatarFallback class="text-[10px] font-semibold"
                        >--</AvatarFallback
                    >
                </Avatar>
                <span>Unassigned</span>
                <Check
                    v-if="modelValue === null"
                    class="ml-auto h-4 w-4 text-primary"
                />
            </DropdownMenuItem>

            <DropdownMenuItem
                v-for="member in teamMembers"
                :key="member.id"
                class="cursor-pointer"
                @click="selectMember(member.id)"
            >
                <Avatar class="h-6 w-6 border border-border">
                    <AvatarFallback class="text-[10px] font-semibold">
                        {{ getInitials(member.name) }}
                    </AvatarFallback>
                </Avatar>
                <span class="truncate">{{ member.name }}</span>
                <Check
                    v-if="modelValue === member.id"
                    class="ml-auto h-4 w-4 text-primary"
                />
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
