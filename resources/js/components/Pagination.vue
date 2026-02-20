<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { PaginationLink } from '@/types';
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    currentPage: number;
    lastPage: number;
    prevPageUrl: string | null;
    nextPageUrl: string | null;
    links: PaginationLink[];
}>();

const pageLinks = computed(() => {
    return props.links.filter(
        (link) =>
            link.page !== null &&
            !link.label.includes('Previous') &&
            !link.label.includes('Next'),
    );
});
</script>

<template>
    <div class="mt-6 flex items-center justify-end gap-2">
        <Button
            variant="outline"
            size="sm"
            :disabled="!prevPageUrl"
            @click="prevPageUrl && router.get(prevPageUrl)"
        >
            <ChevronLeft class="size-4" />
        </Button>

        <div class="flex items-center gap-1">
            <Button
                v-for="link in pageLinks"
                :key="link.label"
                :variant="link.active ? 'default' : 'outline'"
                size="icon-sm"
                @click="link.url && router.get(link.url)"
            >
                {{ link.page }}
            </Button>
        </div>

        <Button
            variant="outline"
            size="sm"
            :disabled="!nextPageUrl"
            @click="nextPageUrl && router.get(nextPageUrl)"
        >
            <ChevronRight class="size-4" />
        </Button>
    </div>
</template>
