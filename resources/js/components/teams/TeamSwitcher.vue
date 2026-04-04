<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Team } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();

const currentTeam = computed(() => page.props.currentTeam as Team | null);
const teams = computed(() => (page.props.teams as Team[]) ?? []);
const isTeamsSettingsPage = computed(() =>
    page.url.startsWith('/settings/teams'),
);

const switchTeam = (team: Team) => {
    router.post(`/settings/teams/${team.id}/switch`);
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                class="w-full justify-start px-2 data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
            >
                <Users
                    class="hidden size-4 shrink-0 group-data-[collapsible=icon]:block"
                />
                <div
                    class="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden"
                >
                    <span class="truncate font-semibold">
                        {{ currentTeam?.name ?? 'Select team' }}
                    </span>
                </div>
                <ChevronsUpDown
                    class="ml-auto group-data-[collapsible=icon]:hidden"
                />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            class="w-[--reka-dropdown-menu-trigger-width] min-w-56 rounded-lg"
            align="start"
            side="right"
            :side-offset="4"
        >
            <DropdownMenuLabel class="text-xs text-muted-foreground">
                Teams
            </DropdownMenuLabel>
            <DropdownMenuItem
                v-for="team in teams"
                :key="team.id"
                class="cursor-pointer gap-2 p-2"
                @click="switchTeam(team)"
            >
                {{ team.name }}
                <Check
                    v-if="currentTeam?.id === team.id"
                    class="ml-auto h-4 w-4"
                />
            </DropdownMenuItem>
            <DropdownMenuSeparator v-if="!isTeamsSettingsPage" />
            <DropdownMenuItem as-child v-if="!isTeamsSettingsPage">
                <a href="/settings/teams" class="w-full">Manage teams</a>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
