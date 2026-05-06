<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { Tag } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    tags: Tag[];
    removable?: boolean;
}>();

const emit = defineEmits<{
    remove: [tagId: number];
}>();

const tagList = computed(() => props.tags ?? []);
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Badge
            v-for="tag in tagList"
            :key="tag.id"
            class="flex items-center gap-1 border-transparent text-white"
            :style="{ backgroundColor: tag.color }"
        >
            <span>{{ tag.name }}</span>
            <button
                v-if="removable"
                type="button"
                class="ml-1 opacity-70 transition-opacity hover:opacity-100 focus:opacity-100 focus:outline-none"
                :aria-label="`Remove tag ${tag.name}`"
                @click="emit('remove', tag.id)"
            >
                ✕
            </button>
        </Badge>

        <p v-if="!tagList.length" class="text-xs text-muted-foreground">
            No tags
        </p>
    </div>
</template>
