<script setup lang="ts">
import ParagraphPreviewNodeView from '@/components/tasks/ParagraphPreviewNodeView.vue';
import { Button } from '@/components/ui/button';
import { Paragraph } from '@tiptap/extension-paragraph';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, VueNodeViewRenderer, useEditor } from '@tiptap/vue-3';
import {
    Bold,
    Code2,
    Italic,
    List,
    ListOrdered,
    Pilcrow,
    Paperclip,
} from 'lucide-vue-next';
import { onBeforeUnmount, ref, watch, computed } from 'vue';
import TaskAttachmentsPanel from '@/components/tasks/TaskAttachmentsPanel.vue';
import type { TaskAttachment } from '@/types';
import { toast } from 'vue-sonner';

const isAllowedFile = (file: File): boolean => {
    const allowedTypes = [
        'application/pdf',
        'image/png',
        'image/svg+xml',
        'image/jpeg',
        'image/jpg'
    ];
    if (allowedTypes.includes(file.type)) return true;

    const ext = file.name.split('.').pop()?.toLowerCase();
    return ['pdf', 'png', 'svg', 'jpeg', 'jpg'].includes(ext || '');
};

const filterFiles = (files: File[]): File[] => {
    const allowed: File[] = [];
    const rejected: string[] = [];

    for (const file of files) {
        if (isAllowedFile(file)) {
            allowed.push(file);
        } else {
            rejected.push(file.name);
        }
    }

    if (rejected.length > 0) {
        toast.error(`Only PDF and image files (PNG, SVG, JPEG) are allowed. Rejected: ${rejected.join(', ')}`);
    }

    return allowed;
};

const ParagraphWithPreview = Paragraph.extend({
    addNodeView() {
        return VueNodeViewRenderer(ParagraphPreviewNodeView);
    },
});

const props = withDefaults(
    defineProps<{
        modelValue: string;
        placeholder?: string;
        minHeight?: string;
        existingAttachments?: TaskAttachment[];
        pendingFiles?: File[];
        removedIds?: string[];
        showAttachments?: boolean;
    }>(),
    {
        showAttachments: false,
        existingAttachments: () => [],
        pendingFiles: () => [],
        removedIds: () => [],
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'update:attachments', files: File[]): void;
    (e: 'update:removed-attachment-ids', ids: string[]): void;
}>();

const hasAttachments = computed(() => {
    return props.existingAttachments.length > 0 || props.pendingFiles.length > 0;
});

const fileInputRef = ref<HTMLInputElement | null>(null);

const triggerFileInput = () => fileInputRef.value?.click();

const handleFileInputChange = (event: Event) => {
    const files = (event.target as HTMLInputElement).files;
    if (files && files.length > 0) {
        const allowedFiles = filterFiles(Array.from(files));
        if (allowedFiles.length > 0) {
            emit('update:attachments', [...props.pendingFiles, ...allowedFiles]);
        }
        (event.target as HTMLInputElement).value = '';
    }
};

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            // disable, we use our custom one with preview NodeView
            paragraph: false,
            link: false,
        }),
        ParagraphWithPreview,
        Placeholder.configure({
            placeholder: props.placeholder ?? 'Write a description...',
        }),
        Link.configure({
            openOnClick: true,
            autolink: true,
            linkOnPaste: true,
            HTMLAttributes: {
                class: 'text-blue-600 dark:text-blue-400 underline underline-offset-4 hover:text-blue-800 dark:hover:text-blue-300 transition-colors cursor-pointer',
            },
        }),
    ],
    editorProps: {
        attributes: {
            class: 'w-full bg-background px-3 py-2 text-sm focus-visible:outline-none',
            style: `min-height: ${props.minHeight ?? '6rem'};`,
        },
        handlePaste(view, event) {
            if (!props.showAttachments) return false;
            const items = event.clipboardData?.items;
            if (!items) return false;

            const files: File[] = [];
            for (const item of items) {
                if (item.kind === 'file') {
                    const file = item.getAsFile();
                    if (file) {
                        files.push(file);
                    }
                }
            }

            if (files.length > 0) {
                const allowedFiles = filterFiles(files);
                if (allowedFiles.length > 0) {
                    emit('update:attachments', [...props.pendingFiles, ...allowedFiles]);
                }
                return true;
            }
            return false;
        },
        handleDrop(view, event, slice, moved) {
            if (!props.showAttachments) return false;
            
            if (!moved && event.dataTransfer?.files?.length) {
                const files = Array.from(event.dataTransfer.files);
                const allowedFiles = filterFiles(files);
                if (allowedFiles.length > 0) {
                    emit('update:attachments', [...props.pendingFiles, ...allowedFiles]);
                }
                return true;
            }

            const html = event.dataTransfer?.getData('text/html');
            if (html && html.includes('<img')) {
                return true;
            }
            
            return false;
        },
    },
    onUpdate: ({ editor }) => {
        const content = editor.isEmpty ? '' : editor.getHTML();
        emit('update:modelValue', content);
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) return;
        const current = editor.value.isEmpty ? '' : editor.value.getHTML();
        if (value !== current) {
            editor.value.commands.setContent(value || '', {
                emitUpdate: false,
            });
        }
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <div
        class="overflow-hidden rounded-md border border-input bg-background shadow-xs transition-[color,box-shadow]"
    >
        <div class="flex flex-wrap items-center gap-1 border-b border-input p-2">
            <Button
                type="button"
                size="icon-sm"
                variant="ghost"
                :class="editor?.isActive('paragraph') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().setParagraph().run()"
                title="Paragraph"
            >
                <Pilcrow class="size-4" />
            </Button>

            <Button
                type="button"
                size="icon-sm"
                variant="ghost"
                :class="editor?.isActive('bold') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().toggleBold().run()"
                title="Bold"
            >
                <Bold class="size-4" />
            </Button>

            <Button
                type="button"
                size="icon-sm"
                variant="ghost"
                :class="editor?.isActive('italic') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().toggleItalic().run()"
                title="Italic"
            >
                <Italic class="size-4" />
            </Button>

            <Button
                type="button"
                size="icon-sm"
                variant="ghost"
                :class="editor?.isActive('bulletList') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().toggleBulletList().run()"
                title="Bullet list"
            >
                <List class="size-4" />
            </Button>

            <Button
                type="button"
                size="icon-sm"
                variant="ghost"
                :class="editor?.isActive('orderedList') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().toggleOrderedList().run()"
                title="Numbered list"
            >
                <ListOrdered class="size-4" />
            </Button>

            <Button
                type="button"
                size="icon-sm"
                variant="ghost"
                :class="editor?.isActive('codeBlock') ? 'bg-accent' : ''"
                @click="editor?.chain().focus().toggleCodeBlock().run()"
                title="Code block"
            >
                <Code2 class="size-4" />
            </Button>

            <Button
                v-if="props.showAttachments"
                type="button"
                size="icon-sm"
                variant="ghost"
                title="Attach file or image"
                @click="triggerFileInput"
            >
                <Paperclip class="size-4" />
            </Button>

            <input
                ref="fileInputRef"
                type="file"
                multiple
                accept="application/pdf,image/png,image/svg+xml,image/jpeg,image/jpg"
                class="hidden"
                @change="handleFileInputChange"
            />
        </div>

        <EditorContent
            :editor="editor"
            class="task-rich-text"
            :style="{ '--task-editor-min-height': props.minHeight ?? '6rem' }"
        />

        <div v-if="props.showAttachments && hasAttachments" class="px-3 pb-3">
            <TaskAttachmentsPanel
                :existing-attachments="props.existingAttachments"
                :pending-files="props.pendingFiles"
                :removed-ids="props.removedIds"
                @update:pending-files="emit('update:attachments', $event)"
                @update:removed-ids="emit('update:removed-attachment-ids', $event)"
            />
        </div>
    </div>
</template>

<style scoped>
.task-rich-text :deep(.ProseMirror) {
    min-height: var(--task-editor-min-height, 6rem);
    margin: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
}

.task-rich-text :deep(.ProseMirror p.is-editor-empty:first-child::before) {
    color: hsl(var(--muted-foreground));
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

.task-rich-text :deep(.ProseMirror ul) {
    list-style: disc;
    margin-left: 1.25rem;
}

.task-rich-text :deep(.ProseMirror ol) {
    list-style: decimal;
    margin-left: 1.25rem;
}

.task-rich-text :deep(.ProseMirror pre) {
    background: hsl(var(--muted));
    border-radius: 0.375rem;
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
        'Liberation Mono', 'Courier New', monospace;
    padding: 0.5rem 0.75rem;
}
</style>
