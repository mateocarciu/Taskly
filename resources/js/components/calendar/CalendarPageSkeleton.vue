<script setup lang="ts">
const skeletonClass = 'animate-pulse rounded-md bg-muted/70';
</script>

<template>
    <!-- Grid Skeleton Only -->
    <div
        class="min-h-0 flex-1 overflow-hidden rounded-xl border border-border/60 bg-card"
    >
        <!-- Day headers -->
        <div class="grid grid-cols-7 border-b border-border/60 bg-muted/20">
            <div v-for="i in 7" :key="`header-${i}`" class="p-3 text-center">
                <div :class="[skeletonClass, 'mx-auto h-4 w-12']" />
            </div>
        </div>

        <!-- Calendar cells -->
        <div class="grid h-full grid-cols-7 grid-rows-5">
            <div
                v-for="i in 35"
                :key="`cell-${i}`"
                class="border-r border-b border-border/60 p-2"
            >
                <div
                    :class="[skeletonClass, 'mb-2 ml-auto h-4 w-6 rounded-sm']"
                />
                <div v-if="[3, 8, 12, 17, 24].includes(i)" class="space-y-1">
                    <div :class="[skeletonClass, 'h-5 w-full rounded-sm']" />
                </div>
                <div v-if="[14, 28].includes(i)" class="space-y-1">
                    <div :class="[skeletonClass, 'h-5 w-full rounded-sm']" />
                    <div :class="[skeletonClass, 'h-5 w-4/5 rounded-sm']" />
                </div>
            </div>
        </div>
    </div>
</template>
