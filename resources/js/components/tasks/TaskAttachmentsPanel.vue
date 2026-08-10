<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { FileText, Paperclip, Trash2, RotateCcw, Download } from '@lucide/vue';
import type { TaskAttachment, UnifiedAttachment } from '@/types';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    existingAttachments?: TaskAttachment[];
}>();

const pendingFiles = defineModel<File[]>('pendingFiles', { default: () => [] });
const removedIds = defineModel<string[]>('removedIds', { default: () => [] });

const pendingPreviews = ref<Record<number, string>>({});

watch(
    pendingFiles,
    (newFiles) => {
        Object.values(pendingPreviews.value).forEach((url) =>
            URL.revokeObjectURL(url),
        );
        const previews: Record<number, string> = {};
        newFiles.forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                previews[index] = URL.createObjectURL(file);
            }
        });
        pendingPreviews.value = previews;
    },
    { deep: true, immediate: true },
);

onBeforeUnmount(() => {
    Object.values(pendingPreviews.value).forEach((url) =>
        URL.revokeObjectURL(url),
    );
});

const formatBytes = (bytes: number, decimals = 1) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

const isImageFile = (mime: string, name: string) => {
    return (
        mime?.startsWith('image/') ||
        /\.(jpg|jpeg|png|gif|webp|svg|bmp)$/i.test(name.toLowerCase())
    );
};

const isExistingRemoved = (id: string) => {
    return removedIds.value.includes(id);
};

const unifiedAttachments = computed<UnifiedAttachment[]>(() => {
    const list: UnifiedAttachment[] = [];

    if (props.existingAttachments) {
        props.existingAttachments.forEach((a) => {
            list.push({
                id: a.id,
                key: `existing-${a.id}`,
                filename: a.filename,
                size: a.size,
                mime_type: a.mime_type,
                url: a.url,
                isImage: isImageFile(a.mime_type, a.filename),
                isPending: false,
                isRemoved: isExistingRemoved(a.id),
            });
        });
    }

    pendingFiles.value.forEach((file, index) => {
        list.push({
            id: `pending-${index}`,
            key: `pending-${index}`,
            filename: file.name,
            size: file.size,
            mime_type: file.type,
            previewUrl: pendingPreviews.value[index],
            isImage: isImageFile(file.type, file.name),
            isPending: true,
            isRemoved: false,
            pendingIndex: index,
        });
    });

    return list;
});

const hasAttachments = computed(() => {
    return unifiedAttachments.value.length > 0;
});

const removePendingFile = (index: number) => {
    pendingFiles.value = pendingFiles.value.filter((_, i) => i !== index);
};

const toggleRemoveExisting = (id: string) => {
    if (removedIds.value.includes(id)) {
        removedIds.value = removedIds.value.filter((x) => x !== id);
    } else {
        removedIds.value = [...removedIds.value, id];
    }
};
</script>

<template>
    <div v-if="hasAttachments" class="space-y-2.5">
        <div
            class="flex items-center gap-1.5 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase select-none"
        >
            <Paperclip class="size-3.5 text-muted-foreground/75" />
            <span>Attachments</span>
            <Badge
                variant="secondary"
                class="py-0.2 rounded-full px-1.5 text-[10px] font-semibold"
            >
                {{ unifiedAttachments.length }}
            </Badge>
        </div>

        <div class="flex flex-wrap gap-2.5">
            <div
                v-for="item in unifiedAttachments"
                :key="item.key"
                class="group relative flex h-[140px] w-[130px] flex-col overflow-hidden rounded-lg border border-border bg-card/60 transition-all duration-200 select-none hover:border-primary/30 hover:bg-muted/10"
                :class="{
                    'border-destructive/20 bg-destructive/5 opacity-50':
                        item.isRemoved,
                }"
            >
                <a
                    v-if="!item.isRemoved && (item.url || item.previewUrl)"
                    :href="item.url || item.previewUrl"
                    target="_blank"
                    class="relative flex h-[90px] w-full cursor-pointer items-center justify-center overflow-hidden border-b border-border/40 bg-muted/20"
                >
                    <img
                        v-if="item.isImage && (item.url || item.previewUrl)"
                        :src="item.url || item.previewUrl"
                        :alt="item.filename"
                        class="h-full w-full object-cover"
                    />
                    <FileText
                        v-else
                        class="size-7 text-rose-500/90 transition-colors dark:text-rose-400/90"
                    />
                </a>
                <div
                    v-else
                    class="relative flex h-[90px] w-full items-center justify-center overflow-hidden border-b border-border/40 bg-muted/20"
                >
                    <img
                        v-if="item.isImage && (item.url || item.previewUrl)"
                        :src="item.url || item.previewUrl"
                        :alt="item.filename"
                        class="h-full w-full object-cover"
                    />
                    <FileText
                        v-else
                        class="size-7 text-rose-500/90 transition-colors dark:text-rose-400/90"
                    />
                </div>

                <div
                    class="absolute top-1 right-1 z-10 flex items-center gap-1"
                >
                    <a
                        v-if="!item.isPending && !item.isRemoved"
                        :href="item.url"
                        target="_blank"
                        download
                        class="flex items-center justify-center rounded-md border border-border bg-background/90 p-1 text-muted-foreground shadow-xs transition-all duration-150 hover:bg-background hover:text-foreground"
                        title="Download file"
                        @click.stop
                    >
                        <Download class="size-3" />
                    </a>

                    <button
                        v-if="item.isPending"
                        type="button"
                        class="flex cursor-pointer items-center justify-center rounded-md border border-border bg-background/90 p-1 text-muted-foreground shadow-xs transition-all duration-150 hover:bg-background hover:text-destructive"
                        title="Remove staging"
                        @click.stop="removePendingFile(item.pendingIndex!)"
                    >
                        <Trash2 class="size-3" />
                    </button>
                    <button
                        v-else
                        type="button"
                        class="flex cursor-pointer items-center justify-center rounded-md border border-border bg-background/90 p-1 shadow-xs transition-all duration-150 hover:bg-background"
                        :class="[
                            item.isRemoved
                                ? 'border-primary/20 bg-primary/10 text-primary'
                                : 'border-border text-muted-foreground hover:text-destructive',
                        ]"
                        :title="item.isRemoved ? 'Restore file' : 'Delete file'"
                        @click.stop="toggleRemoveExisting(item.id as string)"
                    >
                        <RotateCcw v-if="item.isRemoved" class="size-3" />
                        <Trash2 v-else class="size-3" />
                    </button>
                </div>

                <div
                    class="flex min-w-0 flex-1 flex-col justify-center bg-card p-1.5"
                >
                    <a
                        v-if="!item.isRemoved && (item.url || item.previewUrl)"
                        :href="item.url || item.previewUrl"
                        target="_blank"
                        class="block truncate text-[10px] leading-tight font-medium text-foreground hover:underline"
                        :title="item.filename"
                    >
                        {{ item.filename }}
                    </a>
                    <span
                        v-else
                        class="block truncate text-[10px] leading-tight font-medium text-muted-foreground"
                        :title="item.filename"
                        :class="{ 'line-through': item.isRemoved }"
                    >
                        {{ item.filename }}
                    </span>

                    <div
                        class="mt-0.5 flex items-center justify-between text-[8px] leading-none text-muted-foreground/80"
                    >
                        <span>{{ formatBytes(item.size) }}</span>
                        <span
                            v-if="item.isPending"
                            class="py-0.2 shrink-0 rounded bg-primary/10 px-1 text-[7px] font-bold text-primary"
                        >
                            NEW
                        </span>
                        <span
                            v-if="item.isRemoved"
                            class="py-0.2 shrink-0 rounded bg-destructive/10 px-1 text-[7px] font-bold text-destructive"
                        >
                            DEL
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
