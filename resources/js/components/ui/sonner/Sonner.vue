<script setup lang="ts">
import { Toaster as Sonner, type ToasterProps } from 'vue-sonner';
import 'vue-sonner/style.css';

const props = defineProps<ToasterProps>();
</script>

<template>
    <Sonner
        class="toaster group"
        v-bind="props"
        :toast-options="{
            classes: {
                toast: 'group toast group-[.toaster]:bg-background group-[.toaster]:text-foreground group-[.toaster]:border-border group-[.toaster]:shadow-lg group-[.toaster]:rounded-lg',
                description: 'group-[.toast]:text-muted-foreground',
                actionButton:
                    'group-[.toast]:bg-primary group-[.toast]:text-primary-foreground',
                cancelButton:
                    'group-[.toast]:bg-muted group-[.toast]:text-muted-foreground',
                success:
                    'group-[.toaster]:border-green-500/20 group-[.toaster]:bg-green-500/10 group-[.toaster]:text-green-600 dark:group-[.toaster]:text-green-400',
                error: 'group-[.toaster]:border-destructive/20 group-[.toaster]:bg-destructive/10 group-[.toaster]:text-destructive',
            },
        }"
    />
</template>
