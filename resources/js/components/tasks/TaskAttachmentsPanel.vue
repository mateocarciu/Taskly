<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import {
    File,
    FileText,
    FileCode,
    FileArchive,
    Paperclip,
    Trash2,
    RotateCcw,
    Download
} from 'lucide-vue-next';
import type { TaskAttachment } from '@/types';

const props = defineProps<{
    existingAttachments?: TaskAttachment[];
}>();

const pendingFiles = defineModel<File[]>('pendingFiles', { default: () => [] });
const removedIds = defineModel<string[]>('removedIds', { default: () => [] });

const pendingPreviews = ref<Record<number, string>>({});

// Clean up object URLs when pending files change
watch(pendingFiles, (newFiles) => {
    // Revoke old previews
    Object.values(pendingPreviews.value).forEach(url => URL.revokeObjectURL(url));
    
    // Generate new previews for images
    const previews: Record<number, string> = {};
    newFiles.forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            previews[index] = URL.createObjectURL(file);
        }
    });
    pendingPreviews.value = previews;
}, { deep: true, immediate: true });

onBeforeUnmount(() => {
    Object.values(pendingPreviews.value).forEach(url => URL.revokeObjectURL(url));
});

// Format file size
const formatBytes = (bytes: number, decimals = 1) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

// Check if mime type or filename represents an image
const isImageFile = (mimeType: string, filenameStr: string) => {
    const name = filenameStr.toLowerCase();
    return mimeType?.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp|svg|bmp)$/i.test(name);
};

// Select icon based on mime type or extension
const getFileIcon = (mimeType: string, filenameStr: string) => {
    const name = filenameStr.toLowerCase();
    if (mimeType?.includes('pdf') || /\.pdf$/i.test(name)) return FileText;
    if (
        mimeType?.includes('zip') || 
        mimeType?.includes('tar') || 
        mimeType?.includes('gzip') || 
        mimeType?.includes('rar') || 
        /\.(zip|rar|tar|gz|7z)$/i.test(name)
    ) return FileArchive;
    if (
        mimeType?.includes('json') || 
        mimeType?.includes('javascript') || 
        mimeType?.includes('html') || 
        mimeType?.includes('css') || 
        mimeType?.includes('xml') ||
        /\.(json|js|ts|html|css|py|php|go|rb|rs)$/i.test(name)
    ) return FileCode;
    return File;
};

// Filter existing into images and documents
const existingImages = computed(() => {
    return (props.existingAttachments || []).filter(a => isImageFile(a.mime_type, a.filename));
});

const existingDocuments = computed(() => {
    return (props.existingAttachments || []).filter(a => !isImageFile(a.mime_type, a.filename));
});

// Filter pending into images and documents
const pendingImages = computed(() => {
    return pendingFiles.value
        .map((file, index) => ({ file, index, previewUrl: pendingPreviews.value[index] }))
        .filter(item => isImageFile(item.file.type, item.file.name));
});

const pendingDocuments = computed(() => {
    return pendingFiles.value
        .map((file, index) => ({ file, index }))
        .filter(item => !isImageFile(item.file.type, item.file.name));
});

const hasAttachments = computed(() => {
    return (props.existingAttachments || []).length > 0 || pendingFiles.value.length > 0;
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

const isExistingRemoved = (id: string) => {
    return removedIds.value.includes(id);
};
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-muted-foreground flex items-center gap-1.5 uppercase tracking-wider">
                <Paperclip class="size-3.5" />
                Attachments
                <span 
                    v-if="hasAttachments" 
                    class="text-[10px] text-muted-foreground bg-muted px-1.5 py-0.2 rounded-full font-bold"
                >
                    {{ (existingAttachments || []).length + pendingFiles.length }}
                </span>
            </span>
        </div>

        <div v-if="hasAttachments" class="space-y-4">
            <!-- 1. Image Attachments Section (Grid View) -->
            <div v-if="existingImages.length > 0 || pendingImages.length > 0" class="space-y-1.5">
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2.5">
                    <!-- Existing Images -->
                    <div
                        v-for="img in existingImages"
                        :key="img.id"
                        class="aspect-square rounded-lg border border-border/80 bg-muted/20 relative group overflow-hidden transition-all hover:border-primary/50 shadow-xs"
                        :class="{ 'opacity-40 border-destructive/30': isExistingRemoved(img.id) }"
                    >
                        <img 
                            :src="img.url" 
                            :alt="img.filename" 
                            class="w-full h-full object-cover"
                        />
                        
                        <!-- Overlay on hover -->
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex flex-col justify-between p-1.5 text-white">
                            <div class="flex justify-between items-center w-full">
                                <a
                                    v-if="!isExistingRemoved(img.id)"
                                    :href="img.url"
                                    target="_blank"
                                    download
                                    class="p-1 rounded hover:bg-white/20 transition-colors"
                                    title="Download image"
                                    @click.stop
                                >
                                    <Download class="size-3.5 text-white" />
                                </a>
                                <div v-else></div>
                                
                                <button
                                    type="button"
                                    class="p-1 rounded transition-colors"
                                    :class="[
                                        isExistingRemoved(img.id)
                                            ? 'text-primary hover:bg-white/20'
                                            : 'text-destructive-foreground hover:bg-destructive/80 bg-destructive/20'
                                    ]"
                                    :title="isExistingRemoved(img.id) ? 'Restore image' : 'Delete image'"
                                    @click.stop="toggleRemoveExisting(img.id)"
                                >
                                    <RotateCcw v-if="isExistingRemoved(img.id)" class="size-3.5" />
                                    <Trash2 v-else class="size-3.5" />
                                </button>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-medium truncate" :title="img.filename">
                                    {{ img.filename }}
                                </p>
                                <p class="text-[8px] text-zinc-300 mt-0.5 flex justify-between">
                                    <span>{{ formatBytes(img.size) }}</span>
                                    <span v-if="isExistingRemoved(img.id)" class="text-destructive font-bold">DELETED</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Staged Staged Images -->
                    <div
                        v-for="img in pendingImages"
                        :key="`pending-img-${img.index}`"
                        class="aspect-square rounded-lg border border-primary/20 bg-primary/5 relative group overflow-hidden transition-all hover:border-primary/50 shadow-xs"
                    >
                        <img 
                            v-if="img.previewUrl"
                            :src="img.previewUrl" 
                            :alt="img.file.name" 
                            class="w-full h-full object-cover"
                        />
                        
                        <!-- Overlay on hover -->
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex flex-col justify-between p-1.5 text-white">
                            <div class="flex justify-end w-full">
                                <button
                                    type="button"
                                    class="p-1 rounded text-destructive-foreground hover:bg-destructive/80 bg-destructive/20 transition-colors"
                                    title="Remove staging"
                                    @click.stop="removePendingFile(img.index)"
                                >
                                    <Trash2 class="size-3.5" />
                                </button>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-medium truncate" :title="img.file.name">
                                    {{ img.file.name }}
                                </p>
                                <div class="flex justify-between items-center mt-0.5 text-[8px]">
                                    <span class="text-zinc-300">{{ formatBytes(img.file.size) }}</span>
                                    <span class="text-primary font-bold bg-primary/20 px-1 py-0.2 rounded">NEW</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Non-Image Attachments Section (List View) -->
            <div v-if="existingDocuments.length > 0 || pendingDocuments.length > 0" class="space-y-1.5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <!-- Existing Documents -->
                    <div
                        v-for="doc in existingDocuments"
                        :key="doc.id"
                        class="flex items-center justify-between p-2.5 rounded-md border border-border/80 bg-background/50 hover:bg-muted/30 transition-colors group"
                        :class="{ 'opacity-50 bg-muted/10 line-through': isExistingRemoved(doc.id) }"
                    >
                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                            <component
                                :is="getFileIcon(doc.mime_type, doc.filename)"
                                class="size-4 shrink-0 text-muted-foreground group-hover:text-primary transition-colors"
                            />
                            <div class="min-w-0 flex-1">
                                <a
                                    v-if="!isExistingRemoved(doc.id)"
                                    :href="doc.url"
                                    target="_blank"
                                    class="text-xs font-medium hover:underline text-foreground block truncate"
                                >
                                    {{ doc.filename }}
                                </a>
                                <span 
                                    v-else 
                                    class="text-xs font-medium text-muted-foreground block truncate line-through"
                                >
                                    {{ doc.filename }}
                                </span>
                                <div class="text-[10px] text-muted-foreground mt-0.5">
                                    {{ formatBytes(doc.size) }}
                                    <span v-if="isExistingRemoved(doc.id)" class="text-destructive font-semibold ml-1">
                                        (to be deleted)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1 ml-2 shrink-0">
                            <a
                                v-if="!isExistingRemoved(doc.id)"
                                :href="doc.url"
                                target="_blank"
                                download
                                class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                                title="Download file"
                            >
                                <Download class="size-3.5" />
                            </a>
                            <button
                                type="button"
                                class="p-1 rounded transition-colors"
                                :class="[
                                    isExistingRemoved(doc.id)
                                        ? 'text-primary hover:bg-primary/10'
                                        : 'text-muted-foreground hover:text-destructive hover:bg-destructive/10'
                                ]"
                                :title="isExistingRemoved(doc.id) ? 'Restore file' : 'Delete file'"
                                @click="toggleRemoveExisting(doc.id)"
                            >
                                <RotateCcw v-if="isExistingRemoved(doc.id)" class="size-3.5" />
                                <Trash2 v-else class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Staged Staged Documents -->
                    <div
                        v-for="doc in pendingDocuments"
                        :key="`pending-doc-${doc.index}`"
                        class="flex items-center justify-between p-2.5 rounded-md border border-primary/20 bg-primary/5 hover:bg-primary/10 transition-colors group"
                    >
                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                            <component
                                :is="getFileIcon(doc.file.type, doc.file.name)"
                                class="size-4 shrink-0 text-primary"
                            />
                            <div class="min-w-0 flex-1">
                                <span class="text-xs font-medium text-foreground block truncate">
                                    {{ doc.file.name }}
                                </span>
                                <div class="flex items-center gap-1.5 text-[10px] mt-0.5">
                                    <span class="text-muted-foreground">{{ formatBytes(doc.file.size) }}</span>
                                    <span class="text-primary font-semibold bg-primary/15 px-1 py-0.2 rounded text-[9px]">
                                        New
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="p-1 rounded text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors ml-2 shrink-0"
                            title="Remove staged file"
                            @click="removePendingFile(doc.index)"
                        >
                            <Trash2 class="size-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
