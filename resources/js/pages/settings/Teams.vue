<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem, Team } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

interface TeamItem extends Team {
    users_count: number;
    is_current: boolean;
}

const props = defineProps<{
    teams: TeamItem[];
    currentTeamId: number | null;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Team settings',
        href: '/settings/teams',
    },
];

const createForm = useForm({
    name: '',
});

const renameForm = useForm({
    name:
        props.teams.find((team) => team.id === props.currentTeamId)?.name ?? '',
});

const createTeam = () => {
    createForm.post('/settings/teams', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const renameCurrentTeam = () => {
    if (!props.currentTeamId) return;

    renameForm.patch(`/settings/teams/${props.currentTeamId}`, {
        preserveScroll: true,
    });
};

const switchTeam = (teamId: number) => {
    useForm({}).post(`/settings/teams/${teamId}/switch`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Team settings" />

        <SettingsLayout>
            <div class="space-y-8">
                <div class="space-y-4">
                    <HeadingSmall
                        title="Switch team"
                        description="Choose your active team for tasks and dashboard data"
                    />

                    <div class="grid gap-3">
                        <Card v-for="team in teams" :key="team.id">
                            <CardHeader class="pb-3">
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div>
                                        <CardTitle>{{ team.name }}</CardTitle>
                                        <CardDescription>
                                            {{ team.users_count }} member{{
                                                team.users_count > 1 ? 's' : ''
                                            }}
                                        </CardDescription>
                                    </div>
                                    <Badge
                                        v-if="team.is_current"
                                        variant="secondary"
                                        >Current</Badge
                                    >
                                </div>
                            </CardHeader>
                            <CardContent>
                                <Button
                                    variant="outline"
                                    :disabled="team.is_current"
                                    @click="switchTeam(team.id)"
                                >
                                    {{
                                        team.is_current
                                            ? 'Active team'
                                            : 'Switch to this team'
                                    }}
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <div class="space-y-4">
                    <HeadingSmall
                        title="Rename current team"
                        description="Update the name of the team you are currently using"
                    />

                    <form class="space-y-3" @submit.prevent="renameCurrentTeam">
                        <div class="grid gap-2">
                            <Label for="rename-team-name">Team name</Label>
                            <Input
                                id="rename-team-name"
                                v-model="renameForm.name"
                                type="text"
                                placeholder="New team name"
                                required
                            />
                        </div>

                        <Button
                            type="submit"
                            :disabled="renameForm.processing || !currentTeamId"
                        >
                            Save changes
                        </Button>
                    </form>
                </div>

                <div class="space-y-4">
                    <HeadingSmall
                        title="Create a team"
                        description="Create a new team and switch to it immediately"
                    />

                    <form class="space-y-3" @submit.prevent="createTeam">
                        <div class="grid gap-2">
                            <Label for="create-team-name">Team name</Label>
                            <Input
                                id="create-team-name"
                                v-model="createForm.name"
                                type="text"
                                placeholder="e.g. Product Team"
                                required
                            />
                        </div>

                        <Button type="submit" :disabled="createForm.processing">
                            Create team
                        </Button>
                    </form>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
