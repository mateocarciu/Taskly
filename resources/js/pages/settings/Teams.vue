<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import CreateTeamDialog from '@/components/teams/CreateTeamDialog.vue';
import TeamSwitcher from '@/components/teams/TeamSwitcher.vue';
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
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { PencilLine, Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    teams: Team[];
    currentTeamId: number | null;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Team settings',
        href: '/settings/teams',
    },
];
const isCreateTeamOpen = ref(false);

const page = usePage();
const user = computed(() => page.props.auth.user);
const currentTeamName = computed(
    () => (page.props.currentTeam as Team | null)?.name ?? '',
);

const renameForm = useForm({
    name: user.value?.team?.name ?? '',
});

const renameCurrentTeam = () => {
    if (!props.currentTeamId) return;

    renameForm.patch(`/settings/teams/${props.currentTeamId}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Team renamed successfully.');
        },
    });
};

watch(currentTeamName, (name) => {
    renameForm.defaults('name', name);
    renameForm.name = name;
    renameForm.clearErrors();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Team settings" />

        <SettingsLayout>
            <CreateTeamDialog v-model:open="isCreateTeamOpen" />

            <div class="space-y-6">
                <div
                    class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
                >
                    <HeadingSmall
                        title="Team settings"
                        description="Switch your active team and manage your current workspace"
                    />

                    <Button @click="isCreateTeamOpen = true">
                        <Plus class="size-4" />
                        Create team
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Active team</CardTitle>
                        <CardDescription>
                            Quickly switch your workspace context using the same
                            selector as the sidebar.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="max-w-sm">
                            <TeamSwitcher />
                        </div>
                        <p class="mt-3 text-sm text-muted-foreground">
                            {{ teams.length }} team{{
                                teams.length > 1 ? 's' : ''
                            }}
                            available.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base"
                            >Rename current team</CardTitle
                        >
                        <CardDescription>
                            Update the name of your currently active team.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            class="space-y-3"
                            @submit.prevent="renameCurrentTeam"
                        >
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
                                :disabled="
                                    renameForm.processing || !currentTeamId
                                "
                            >
                                <PencilLine class="size-4" />
                                Save changes
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
