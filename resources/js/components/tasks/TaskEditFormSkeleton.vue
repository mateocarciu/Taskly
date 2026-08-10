<script setup lang="ts">
const skeletonClass = 'animate-pulse rounded-md bg-muted/70';
</script>

<template>
    <div
        aria-hidden="true"
        class="space-y-6 p-6 lg:flex lg:h-full lg:min-h-0 lg:flex-col lg:space-y-0 lg:p-0"
    >
        <div
            class="lg:flex-1 lg:overflow-y-auto lg:overscroll-contain lg:p-6 lg:pr-8"
        >
            <div class="space-y-4">
                <div class="grid gap-2">
                    <div :class="[skeletonClass, 'h-3.5 w-10']" />
                    <div :class="[skeletonClass, 'h-9 w-full']" />
                </div>

                <div class="grid gap-2">
                    <div :class="[skeletonClass, 'h-3.5 w-24']" />
                    <div :class="[skeletonClass, 'h-64 w-full']" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <div :class="[skeletonClass, 'h-3.5 w-16']" />
                        <div :class="[skeletonClass, 'h-9 w-full']" />
                    </div>

                    <div class="grid gap-2">
                        <div :class="[skeletonClass, 'h-3.5 w-24']" />
                        <div :class="[skeletonClass, 'h-9 w-full']" />
                    </div>
                </div>

                <div class="grid items-start gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <div :class="[skeletonClass, 'h-3.5 w-16']" />
                        <div :class="[skeletonClass, 'h-9 w-full']" />
                    </div>

                    <div class="grid gap-2">
                        <div :class="[skeletonClass, 'h-3.5 w-20']" />
                        <div :class="[skeletonClass, 'h-9 w-full']" />
                    </div>
                </div>
            </div>
        </div>

        <div
            class="pt-4 lg:shrink-0 lg:border-t lg:border-border lg:bg-background lg:px-6 lg:py-4"
        >
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <div :class="[skeletonClass, 'h-9 w-20']" />
                <div :class="[skeletonClass, 'h-9 w-32']" />
            </div>
        </div>
    </div>
</template>
