<script setup lang="ts">
const skeletonClass = 'animate-pulse rounded-md bg-muted/70';
</script>

<template>
    <div aria-hidden="true" class="flex h-full flex-col">
        <div class="px-6 pt-6 lg:pl-8">
            <div class="mb-6 flex flex-wrap items-center gap-3">
                <div :class="[skeletonClass, 'h-6 w-24 rounded-md']" />
                <div :class="[skeletonClass, 'h-6 w-28 rounded-md']" />
                <div :class="[skeletonClass, 'h-6 w-16 rounded-md']" />
            </div>

            <div class="mb-6 flex items-center gap-6 border-b border-border/60">
                <div :class="[skeletonClass, 'h-4 w-20']" />
                <div :class="[skeletonClass, 'h-4 w-20']" />
            </div>
        </div>

        <div class="flex-1 space-y-6 overflow-y-auto px-6 pb-6 lg:pl-8">
            <div class="space-y-3">
                <div :class="[skeletonClass, 'h-3.5 w-32']" />
                <div :class="[skeletonClass, 'h-24 w-full']" />
            </div>

            <div :class="[skeletonClass, 'h-9 w-36']" />

            <div
                v-for="i in 3"
                :key="i"
                class="rounded-lg border border-border/60 bg-card/40 p-3"
            >
                <div class="flex items-start gap-3">
                    <div
                        :class="[skeletonClass, 'size-8 shrink-0 rounded-full']"
                    />
                    <div class="flex-1 space-y-2">
                        <div :class="[skeletonClass, 'h-3.5 w-1/3']" />
                        <div :class="[skeletonClass, 'h-16 w-full']" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
