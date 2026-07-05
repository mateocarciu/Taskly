<script setup lang="ts">
import { onMounted, ref } from 'vue';
import type { PreviewData } from '@/types';
import { X } from 'lucide-vue-next';
import { fetchPreview } from '@/utils/linkPreviewBatcher';

const props = defineProps<{ url: string }>();
const emit = defineEmits<{ (e: 'dismiss'): void }>();

const preview = ref<PreviewData | null>(null);
const loading = ref(true);
const failed = ref(false);

onMounted(async () => {
    try {
        preview.value = await fetchPreview(props.url);
    } catch {
        failed.value = true;
    } finally {
        loading.value = false;
    }
});

const hostname = () => {
    try {
        return new URL(props.url).hostname.replace(/^www\./, '');
    } catch {
        return props.url;
    }
};
</script>

<template>
    <div v-if="!failed" class="lp-wrap group">
        <div v-if="loading" class="lp-card lp-card--loading">
            <div class="lp-skeleton" />
        </div>

        <a
            v-else-if="preview"
            :href="preview.url"
            target="_blank"
            rel="noopener noreferrer"
            class="lp-card bg-accent/30 hover:bg-accent/80 border-border/60 hover:border-border transition-colors duration-200"
        >
            <div v-if="preview.image" class="lp-thumb">
                <img :src="preview.image" alt="" class="lp-thumb-img" loading="lazy" />
            </div>
            <div class="lp-body">
                <div class="lp-meta">
                    <img
                        v-if="preview.favicon"
                        :src="preview.favicon"
                        alt=""
                        class="lp-favicon"
                        loading="lazy"
                    />
                    <span class="lp-hostname">{{ hostname() }}</span>
                </div>
                <div class="lp-title">{{ preview.title ?? hostname() }}</div>
                <div v-if="preview.description" class="lp-desc">{{ preview.description }}</div>
            </div>
        </a>
        <button
            v-if="!loading && preview"
            @click.stop.prevent="emit('dismiss')"
            class="absolute top-1.5 right-1.5 z-10 p-1.5 rounded-md text-muted-foreground transition-all hover:bg-black/5 dark:hover:bg-white/10 hover:text-foreground"
            title="Remove preview"
        >
            <X class="size-3.5" />
        </button>
    </div>
</template>

<style scoped>
.lp-wrap {
    display: block;
    position: relative;
    max-width: 32rem;
    width: fit-content;
}

.lp-card {
    display: flex;
    align-items: stretch;
    border-width: 1px;
    border-style: solid;
    border-radius: 0.5rem;
    overflow: hidden;
    color: var(--foreground);
    text-decoration: none;
    font-size: 0.8125rem;
    line-height: 1.4;
    max-width: 32rem;
    cursor: pointer;
}

.lp-thumb {
    width: 7rem;
    min-width: 7rem;
    background: hsl(var(--muted));
    overflow: hidden;
    flex-shrink: 0;
}

.lp-thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.lp-body {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    padding: 0.625rem 0.875rem;
    min-width: 0;
    flex: 1;
}

.lp-meta {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.lp-favicon {
    width: 1rem;
    height: 1rem;
    border-radius: 2px;
    flex-shrink: 0;
}

.lp-hostname {
    font-size: 0.6875rem;
    color: hsl(var(--muted-foreground));
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.lp-title {
    font-weight: 600;
    color: hsl(var(--foreground));
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.lp-desc {
    color: hsl(var(--muted-foreground));
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-size: 0.75rem;
}

/* Loading skeleton */
.lp-card--loading {
    padding: 0.75rem;
    min-width: 16rem;
    pointer-events: none;
    border: 1px solid hsl(var(--border));
    border-radius: 0.5rem;
    max-width: 32rem;
    background: hsl(var(--card));
}

.lp-skeleton {
    height: 2.5rem;
    border-radius: 0.25rem;
    background: linear-gradient(
        90deg,
        hsl(var(--muted)) 25%,
        hsl(var(--muted-foreground) / 0.08) 50%,
        hsl(var(--muted)) 75%
    );
    background-size: 200% 100%;
    animation: lp-shimmer 1.4s infinite;
}

@keyframes lp-shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
</style>
