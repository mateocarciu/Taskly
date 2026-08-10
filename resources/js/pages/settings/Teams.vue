<script setup lang="ts">
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import CreateTeamDialog from '@/components/teams/CreateTeamDialog.vue';
import TeamSwitcher from '@/components/teams/TeamSwitcher.vue';
import UserSelect from '@/components/teams/UserSelect.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { capitalize } from '@/lib/utils';
import type { BreadcrumbItem, Team, TeamMember } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Crown,
    MoreHorizontal,
    PencilLine,
    Plus,
    Shield,
    ShieldOff,
    Trash2,
    User,
    UserPlus,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

interface AvailableUser {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{
    teams: (Team & { users_count: number; is_current: boolean })[];
    currentTeamId: number | null;
    members: TeamMember[];
    availableUsers: AvailableUser[];
    canManage: boolean;
    isOwner: boolean;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Team settings', href: '/settings/teams' },
];

const isCreateTeamOpen = ref(false);
const isDeleteTeamOpen = ref(false);
const memberToRemove = ref<TeamMember | null>(null);
const isRemoveMemberOpen = ref(false);

const page = usePage();
const user = computed(() => page.props.auth.user);
const currentTeam = computed(() => page.props.currentTeam as Team | null);
const currentTeamName = computed(() => currentTeam.value?.name ?? '');

const renameForm = useForm({ name: currentTeamName.value });

const renameCurrentTeam = () => {
    if (!props.currentTeamId) return;
    renameForm.patch(`/settings/teams/${props.currentTeamId}`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Team renamed successfully.'),
    });
};

watch(currentTeamName, (name) => {
    renameForm.defaults('name', name);
    renameForm.name = name;
    renameForm.clearErrors();
});

const addMemberForm = useForm({ user_id: '' });
const userSelectRef = ref<InstanceType<typeof UserSelect> | null>(null);

const onUserSelected = (user: { id: number }) => {
    addMemberForm.user_id = String(user.id);
};

const addMember = () => {
    if (!props.currentTeamId) return;
    addMemberForm.post(`/settings/teams/${props.currentTeamId}/members`, {
        preserveScroll: true,
        onSuccess: () => {
            addMemberForm.reset();
            userSelectRef.value?.reset();
            toast.success('Member added successfully.');
        },
    });
};

const confirmRemoveMember = (member: TeamMember) => {
    memberToRemove.value = member;
    isRemoveMemberOpen.value = true;
};

const removeMember = () => {
    if (!props.currentTeamId || !memberToRemove.value) return;
    router.delete(
        `/settings/teams/${props.currentTeamId}/members/${memberToRemove.value.id}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                isRemoveMemberOpen.value = false;
                memberToRemove.value = null;
                toast.success('Member removed.');
            },
        },
    );
};

const promote = (member: TeamMember) => {
    if (!props.currentTeamId) return;
    router.patch(
        `/settings/teams/${props.currentTeamId}/members/${member.id}/promote`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success(`${member.name} promoted to admin.`),
        },
    );
};

const demote = (member: TeamMember) => {
    if (!props.currentTeamId) return;
    router.patch(
        `/settings/teams/${props.currentTeamId}/members/${member.id}/demote`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success(`${member.name} demoted to member.`),
        },
    );
};

const deleteTeam = () => {
    if (!props.currentTeamId) return;
    router.delete(`/settings/teams/${props.currentTeamId}`, {
        onSuccess: () => {
            isDeleteTeamOpen.value = false;
            toast.success('Team deleted.');
        },
    });
};

const roleBadgeClass = (role: TeamMember['role']) => {
    if (role === 'owner')
        return 'inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    if (role === 'admin')
        return 'inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    return 'inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground';
};

const canRemoveMember = (member: TeamMember) => {
    if (!props.canManage) return false;
    if (member.id === user.value?.id) return false;
    if (member.role === 'owner' || member.role === 'admin') return false;
    return true;
};

const canPromote = (member: TeamMember) =>
    props.canManage && member.role === 'member';
const canDemote = (member: TeamMember) =>
    props.isOwner && member.role === 'admin';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Team settings" />

        <SettingsLayout>
            <CreateTeamDialog v-model:open="isCreateTeamOpen" />

            <ConfirmDeleteDialog
                v-model:open="isRemoveMemberOpen"
                title="Remove member"
                :description="`Are you sure you want to remove ${memberToRemove?.name} from the team?`"
                confirm-text="Remove"
                @confirm="removeMember"
            />

            <ConfirmDeleteDialog
                v-model:open="isDeleteTeamOpen"
                title="Delete team"
                :description="`Delete '${currentTeamName}'? All tasks, columns, and tags in this team will be permanently deleted.`"
                confirm-text="Delete team"
                @confirm="deleteTeam"
            />

            <div class="space-y-6">
                <div
                    class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
                >
                    <HeadingSmall
                        title="Team settings"
                        description="Switch your active team and manage your current workspace"
                    />
                    <Button v-if="canManage" @click="isCreateTeamOpen = true">
                        <Plus class="size-4" />
                        Create team
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Active team</CardTitle>
                        <CardDescription>
                            Switch your active team or rename the current one.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="max-w-sm">
                            <TeamSwitcher />
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ teams.length }} team{{
                                teams.length > 1 ? 's' : ''
                            }}
                            available.
                        </p>

                        <div
                            v-if="canManage && currentTeamId"
                            class="border-t pt-4"
                        >
                            <form
                                class="space-y-3"
                                @submit.prevent="renameCurrentTeam"
                            >
                                <div class="grid gap-2">
                                    <Label for="rename-team-name"
                                        >Team name</Label
                                    >
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
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="currentTeamId">
                    <CardHeader>
                        <CardTitle class="text-base">Members</CardTitle>
                        <CardDescription>
                            People with access to
                            <strong>{{ currentTeamName }}</strong
                            >.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <ul class="divide-y divide-border rounded-lg border">
                            <li
                                v-for="member in members"
                                :key="member.id"
                                class="flex items-center justify-between gap-3 px-4 py-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">
                                        {{ member.name }}
                                        <span
                                            v-if="member.id === user?.id"
                                            class="ml-1 text-xs text-muted-foreground"
                                            >(you)</span
                                        >
                                    </p>
                                    <p
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {{ member.email }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <span :class="roleBadgeClass(member.role)">
                                        <Crown
                                            v-if="member.role === 'owner'"
                                            class="size-3"
                                        />
                                        <Shield
                                            v-else-if="member.role === 'admin'"
                                            class="size-3"
                                        />
                                        <User v-else class="size-3" />
                                        {{ capitalize(member.role) }}
                                    </span>

                                    <DropdownMenu
                                        v-if="
                                            canPromote(member) ||
                                            canDemote(member) ||
                                            canRemoveMember(member)
                                        "
                                    >
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                class="h-7 w-7 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                                            >
                                                <MoreHorizontal
                                                    class="size-3.5"
                                                />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent
                                            align="end"
                                            class="w-44"
                                        >
                                            <DropdownMenuItem
                                                v-if="canPromote(member)"
                                                class="cursor-pointer"
                                                @click="promote(member)"
                                            >
                                                <Shield class="mr-2 size-3.5" />
                                                <span>Promote to admin</span>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="canDemote(member)"
                                                class="cursor-pointer"
                                                @click="demote(member)"
                                            >
                                                <ShieldOff
                                                    class="mr-2 size-3.5"
                                                />
                                                <span>Demote to member</span>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="canRemoveMember(member)"
                                                class="cursor-pointer text-destructive focus:bg-destructive/10 focus:text-destructive"
                                                @click="
                                                    confirmRemoveMember(member)
                                                "
                                            >
                                                <Trash2 class="mr-2 size-3.5" />
                                                <span>Remove from team</span>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </li>

                            <li
                                v-if="members.length === 0"
                                class="px-4 py-6 text-center text-sm text-muted-foreground"
                            >
                                No members found in this team.
                            </li>
                        </ul>

                        <form
                            v-if="canManage && availableUsers.length > 0"
                            class="flex gap-2"
                            @submit.prevent="addMember"
                        >
                            <div class="flex-1">
                                <UserSelect
                                    ref="userSelectRef"
                                    :users="availableUsers"
                                    :disabled="addMemberForm.processing"
                                    @select="onUserSelected"
                                />
                            </div>
                            <Button
                                type="submit"
                                :disabled="
                                    addMemberForm.processing ||
                                    !addMemberForm.user_id
                                "
                            >
                                <UserPlus class="size-4" />
                                Add
                            </Button>
                        </form>

                        <p
                            v-else-if="canManage && availableUsers.length === 0"
                            class="text-xs text-muted-foreground"
                        >
                            All users are already members of this team.
                        </p>
                    </CardContent>
                </Card>

                <Card
                    v-if="isOwner && currentTeamId"
                    class="border-destructive/40"
                >
                    <CardHeader>
                        <CardTitle class="text-base text-destructive"
                            >Danger zone</CardTitle
                        >
                        <CardDescription>
                            Permanently delete this team and all its data. This
                            action cannot be undone.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button
                            variant="destructive"
                            @click="isDeleteTeamOpen = true"
                        >
                            <Trash2 class="size-4" />
                            Delete team
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
