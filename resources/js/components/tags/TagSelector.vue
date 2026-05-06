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
import type { Tag } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Check, Tag as TagIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import TagBadge from '../tags/TagBadge.vue';

const props = withDefaults(
    defineProps<{
        selected?: Tag[];
        availableTags?: Tag[];
    }>(),
    {
        selected: () => [],
        availableTags: () => [],
    },
);

const emit = defineEmits<{
    'update:selected': [tags: Tag[]];
}>();

const isSelected = (tag: Tag) => props.selected.some((t) => t.id === tag.id);

const toggleTag = (tag: Tag) => {
    const next = isSelected(tag)
        ? props.selected.filter((t) => t.id !== tag.id)
        : [...props.selected, tag];

    emit('update:selected', next);
};

const triggerLabel = computed(() => {
    if (props.selected.length === 0) return 'Add tags...';
    if (props.selected.length === 1) return props.selected[0].name;
    return `${props.selected.length} tags`;
});
</script>

<template>
    <div class="space-y-2">
        <label class="text-sm font-medium">Tags</label>

        <TagBadge
            v-if="selected.length > 0"
            :tags="selected"
            removable
            @remove="(id) => toggleTag(selected.find((t) => t.id === id)!)"
        />

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    type="button"
                    variant="outline"
                    class="h-10 w-full justify-between px-3"
                >
                    <div class="flex min-w-0 items-center gap-2">
                        <TagIcon
                            class="h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        <span class="truncate text-sm">{{ triggerLabel }}</span>
                    </div>
                    <span
                        v-if="selected.length > 0"
                        class="ml-2 shrink-0 rounded-full bg-primary/10 px-1.5 py-0.5 text-xs font-medium text-primary"
                    >
                        {{ selected.length }}
                    </span>
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                class="w-[--reka-dropdown-menu-trigger-width] min-w-52"
                align="start"
            >
                <DropdownMenuLabel>Tags</DropdownMenuLabel>
                <DropdownMenuSeparator />

                <div
                    v-if="availableTags.length === 0"
                    class="px-2 py-4 text-center text-xs text-muted-foreground"
                >
                    No tags available.
                    <Link
                        href="/tags"
                        class="font-medium text-primary hover:underline"
                    >
                        Create one
                    </Link>
                </div>

                <DropdownMenuItem
                    v-for="tag in availableTags"
                    :key="tag.id"
                    class="cursor-pointer"
                    @click.prevent="toggleTag(tag)"
                >
                    <span
                        class="inline-block h-3 w-3 shrink-0 rounded-full"
                        :style="{ backgroundColor: tag.color }"
                    />
                    <span class="truncate">{{ tag.name }}</span>
                    <Check
                        v-if="isSelected(tag)"
                        class="ml-auto h-4 w-4 text-primary"
                    />
                </DropdownMenuItem>

                <template v-if="availableTags.length > 0">
                    <DropdownMenuSeparator />
                    <div class="px-2 py-1.5 text-xs text-muted-foreground">
                        <Link
                            href="/tags"
                            class="font-medium text-primary hover:underline"
                        >
                            Manage tags
                        </Link>
                    </div>
                </template>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
