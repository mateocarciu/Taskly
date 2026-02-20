<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img
        src="https://static.root-me.pro/svg/rmp_vertical_light.svg"
        :class="className"
        alt="App Logo"
    />
</template>
