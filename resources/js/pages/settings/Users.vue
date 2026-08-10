<script setup lang="ts">
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
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
import CreateUserDialog from '@/components/users/CreateUserDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { capitalize } from '@/lib/utils';
import type { BreadcrumbItem, User } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    Crown,
    MoreHorizontal,
    Shield,
    Trash2,
    UserPlus,
    User as UserIcon,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    users: User[];
    isOwner: boolean;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'User Management', href: '/settings/users' },
];

const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const isCreateUserOpen = ref(false);

const userToDelete = ref<User | null>(null);
const isDeleteUserOpen = ref(false);

const confirmDeleteUser = (user: User) => {
    userToDelete.value = user;
    isDeleteUserOpen.value = true;
};

const deleteUser = () => {
    if (!userToDelete.value) return;
    router.delete(`/settings/users/${userToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteUserOpen.value = false;
            userToDelete.value = null;
            toast.success('User deleted.');
        },
    });
};

const userToTransfer = ref<User | null>(null);
const isTransferOwnershipOpen = ref(false);

const confirmTransferOwnership = (user: User) => {
    userToTransfer.value = user;
    isTransferOwnershipOpen.value = true;
};

const transferOwnership = () => {
    if (!userToTransfer.value) return;
    router.post(
        '/settings/users/transfer-ownership',
        { user_id: userToTransfer.value.id },
        {
            preserveScroll: true,
            onSuccess: () => {
                isTransferOwnershipOpen.value = false;
                userToTransfer.value = null;
                toast.success('Ownership transferred successfully.');
            },
        },
    );
};

const roleBadgeClass = (role: User['role']) => {
    if (role === 'owner')
        return 'inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    if (role === 'admin')
        return 'inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    return 'inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground';
};

const canDeleteUser = (user: User) => {
    if (!props.isOwner) return false;
    if (user.id === currentUser.value?.id) return false;
    if (user.role === 'owner') return false;
    return true;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="User Management" />

        <SettingsLayout>
            <CreateUserDialog
                v-model:open="isCreateUserOpen"
                :is-owner="isOwner"
            />

            <ConfirmDeleteDialog
                v-model:open="isDeleteUserOpen"
                title="Delete User"
                :description="`Are you sure you want to delete ${userToDelete?.name}? This action cannot be undone.`"
                confirm-text="Delete User"
                @confirm="deleteUser"
            />

            <ConfirmDeleteDialog
                v-model:open="isTransferOwnershipOpen"
                title="Transfer Ownership"
                :description="`Are you sure you want to transfer instance ownership to ${userToTransfer?.name}? You will be demoted to an admin.`"
                confirm-text="Transfer Ownership"
                @confirm="transferOwnership"
            />

            <div class="space-y-6">
                <div
                    class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
                >
                    <HeadingSmall
                        title="User Management"
                        description="Manage users and access across the entire instance."
                    />
                    <Button @click="isCreateUserOpen = true">
                        <UserPlus class="size-4" />
                        Create User
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">All Users</CardTitle>
                        <CardDescription
                            >People with access to this
                            instance.</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <ul class="divide-y divide-border rounded-lg border">
                            <li
                                v-for="u in users"
                                :key="u.id"
                                class="flex items-center justify-between gap-3 px-4 py-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">
                                        {{ u.name }}
                                        <span
                                            v-if="u.id === currentUser?.id"
                                            class="ml-1 text-xs text-muted-foreground"
                                            >(you)</span
                                        >
                                    </p>
                                    <p
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {{ u.email }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <span :class="roleBadgeClass(u.role)">
                                        <Crown
                                            v-if="u.role === 'owner'"
                                            class="size-3"
                                        />
                                        <Shield
                                            v-else-if="u.role === 'admin'"
                                            class="size-3"
                                        />
                                        <UserIcon v-else class="size-3" />
                                        {{ capitalize(u.role) }}
                                    </span>

                                    <DropdownMenu
                                        v-if="
                                            (isOwner &&
                                                u.id !== currentUser?.id) ||
                                            canDeleteUser(u)
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
                                            class="w-48"
                                        >
                                            <DropdownMenuItem
                                                v-if="
                                                    isOwner &&
                                                    u.id !== currentUser?.id
                                                "
                                                class="cursor-pointer"
                                                @click="
                                                    confirmTransferOwnership(u)
                                                "
                                            >
                                                <ArrowLeftRight
                                                    class="mr-2 size-3.5"
                                                />
                                                <span>Transfer ownership</span>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="canDeleteUser(u)"
                                                class="cursor-pointer text-destructive focus:bg-destructive/10 focus:text-destructive"
                                                @click="confirmDeleteUser(u)"
                                            >
                                                <Trash2 class="mr-2 size-3.5" />
                                                <span>Delete user</span>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </li>

                            <li
                                v-if="users.length === 0"
                                class="px-4 py-6 text-center text-sm text-muted-foreground"
                            >
                                No users found.
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
