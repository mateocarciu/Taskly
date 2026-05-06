<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TagDeleteDialog from '@/components/tags/TagDeleteDialog.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, BreadcrumbItem, Tag } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<
    AppPageProps<{
        tags: Tag[];
    }>
>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tags',
        href: '/tags',
    },
];

const isDialogOpen = ref(false);
const editingTag = ref<Tag | null>(null);
const tagToDelete = ref<Tag | null>(null);
const isDeleteDialogOpen = ref(false);

const form = useForm({
    name: '',
    color: '#6366f1',
});

const resetForm = () => {
    form.reset();
    form.color = '#6366f1';
    form.clearErrors();
    editingTag.value = null;
};

const openCreate = () => {
    resetForm();
    isDialogOpen.value = true;
};

const openEdit = (tag: Tag) => {
    editingTag.value = tag;
    form.name = tag.name;
    form.color = tag.color;
    isDialogOpen.value = true;
};

const openDelete = (tag: Tag) => {
    tagToDelete.value = tag;
    isDeleteDialogOpen.value = true;
};

const submit = () => {
    if (!form.name.trim()) return;

    if (editingTag.value) {
        form.put(`/tags/${editingTag.value.id}`, {
            onSuccess: () => {
                toast.success('Tag updated');
                isDialogOpen.value = false;
                resetForm();
            },
            onError: () => {
                toast.error('Failed to update tag');
            },
        });
        return;
    }

    form.post('/tags', {
        onSuccess: () => {
            toast.success('Tag created');
            isDialogOpen.value = false;
            resetForm();
        },
        onError: () => {
            toast.error('Failed to create tag');
        },
    });
};

const confirmDelete = () => {
    if (!tagToDelete.value) return;

    router.delete(`/tags/${tagToDelete.value.id}`, {
        onSuccess: () => {
            toast.success('Tag deleted');
            isDeleteDialogOpen.value = false;
            tagToDelete.value = null;
        },
        onError: () => {
            toast.error('Failed to delete tag');
        },
    });
};
</script>

<template>
    <Head title="Tags" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Tags</h1>
                    <p class="text-sm text-muted-foreground">
                        Manage your team's task tags
                    </p>
                </div>

                <Dialog v-model:open="isDialogOpen">
                    <DialogTrigger as-child>
                        <Button @click="openCreate">
                            <Plus class="size-4" />
                            New Tag
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="w-[96vw] max-w-[520px]">
                        <DialogHeader>
                            <DialogTitle>
                                {{ editingTag ? 'Edit tag' : 'Create tag' }}
                            </DialogTitle>
                            <DialogDescription>
                                {{
                                    editingTag
                                        ? 'Update the tag details'
                                        : 'Add a new tag for your team'
                                }}
                            </DialogDescription>
                        </DialogHeader>

                        <form class="space-y-4" @submit.prevent="submit">
                            <div class="grid gap-2">
                                <Label for="tag-name">Name</Label>
                                <Input
                                    id="tag-name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="e.g., Urgent"
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="tag-color">Color</Label>
                                <div class="flex items-center gap-3">
                                    <input
                                        id="tag-color"
                                        v-model="form.color"
                                        type="color"
                                        class="h-10 w-16 cursor-pointer rounded border border-input"
                                    />
                                    <span
                                        class="font-mono text-sm text-muted-foreground"
                                    >
                                        {{ form.color }}
                                    </span>
                                </div>
                                <InputError :message="form.errors.color" />
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="isDialogOpen = false"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="
                                        !form.name.trim() || form.processing
                                    "
                                >
                                    {{ editingTag ? 'Update' : 'Create' }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div class="rounded-lg border border-border">
                <div
                    class="border-b border-border px-4 py-3 text-sm font-medium"
                >
                    Team Tags ({{ props.tags.length }})
                </div>
                <div class="divide-y divide-border">
                    <div
                        v-if="props.tags.length === 0"
                        class="px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        No tags yet. Create one to get started!
                    </div>

                    <div
                        v-for="tag in props.tags"
                        :key="tag.id"
                        class="flex items-center justify-between px-4 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="h-3.5 w-3.5 rounded-full border border-border"
                                :style="{ backgroundColor: tag.color }"
                            />
                            <div>
                                <p class="text-sm font-medium">
                                    {{ tag.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ tag.color }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="h-8 w-8"
                                :title="`Edit ${tag.name}`"
                                @click="openEdit(tag)"
                            >
                                <Pencil class="size-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                :title="`Delete ${tag.name}`"
                                @click="openDelete(tag)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <TagDeleteDialog
        v-if="tagToDelete"
        v-model:open="isDeleteDialogOpen"
        :tag="tagToDelete"
        @confirm="confirmDelete"
    />
</template>
