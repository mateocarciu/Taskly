<script setup lang="ts">
import { Button } from '@/components/ui/button';
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import {
    Bold,
    Code2,
    Italic,
    List,
    ListOrdered,
    Pilcrow,
} from 'lucide-vue-next';
import { onBeforeUnmount, watch } from 'vue';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Placeholder.configure({
            placeholder: props.placeholder ?? 'Write a description...',
        }),
    ],
    editorProps: {
        attributes: {
            class: 'min-h-24 w-full bg-background px-3 py-2 text-sm focus-visible:outline-none',
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
        <div
            class="flex flex-wrap items-center gap-1 border-b border-input p-2"
        >
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
        </div>

        <EditorContent :editor="editor" class="task-rich-text rounded-b-md" />
    </div>
</template>

<style scoped>
.task-rich-text :deep(.ProseMirror) {
    min-height: 6rem;
    margin: 0;
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
