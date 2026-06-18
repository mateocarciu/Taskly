<script setup lang="ts">
import LinkPreviewCard from '@/components/tasks/LinkPreviewCard.vue';
import { NodeViewContent, NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3';
import { computed, ref } from 'vue';

const props = defineProps(nodeViewProps);

const locallyDismissed = ref<Set<string>>(new Set());

const links = computed<string[]>(() => {
    const urls: string[] = [];
    const seen = new Set<string>();

    props.node.descendants((node: any) => {
        if (!node.isText) return;
        node.marks?.forEach((mark: any) => {
            if (mark.type.name === 'link') {
                const href: string = mark.attrs?.href ?? '';
                if (href && /^https?:\/\//i.test(href) && !seen.has(href) && !locallyDismissed.value.has(href)) {
                    seen.add(href);
                    urls.push(href);
                }
            }
        });
    });

    return urls;
});

const dismissLink = (url: string) => {
    locallyDismissed.value.add(url);
};
</script>

<template>
    <NodeViewWrapper class="paragraph-with-preview">
        <!-- The actual paragraph content (editable) -->
        <NodeViewContent as="p" />

        <!-- Link previews rendered below this specific paragraph -->
        <div
            v-if="links.length"
            class="paragraph-previews"
            contenteditable="false"
        >
            <LinkPreviewCard
                v-for="url in links"
                :key="url"
                :url="url"
                @dismiss="dismissLink(url)"
            />
        </div>
    </NodeViewWrapper>
</template>

<style scoped>
.paragraph-with-preview {
    display: block;
}

.paragraph-previews {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    margin-top: 0.375rem;
    margin-bottom: 0.375rem;
    user-select: none;
}
</style>
