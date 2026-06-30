<script setup lang="ts">
const skeletonClass = 'animate-pulse rounded-md bg-muted/70';

const columnsData = [
    { cardHeights: ['h-24', 'h-28', 'h-20'] },
    { cardHeights: ['h-32'] },
    { cardHeights: ['h-20', 'h-24', 'h-36', 'h-24'] },
    { cardHeights: ['h-28', 'h-20'] },
];
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6">
        <div class="flex flex-wrap items-center gap-3">
            <div :class="[skeletonClass, 'h-10 w-62']" />
            <div :class="[skeletonClass, 'h-10 w-36']" />
            <div :class="[skeletonClass, 'h-10 w-32']" />
        </div>

        <div class="flex-1 overflow-hidden">
            <div class="flex h-full gap-4">
                <div
                    v-for="(col, colIndex) in columnsData"
                    :key="colIndex"
                    class="flex h-full w-full max-w-[320px] flex-col gap-3 rounded-xl border border-border/60 bg-card/40 p-3"
                >
                    <div :class="[skeletonClass, 'h-6 w-24']" />
                    <div class="space-y-2">
                        <div
                            v-for="(cardHeight, cardIndex) in col.cardHeights"
                            :key="cardIndex"
                            :class="[skeletonClass, cardHeight]"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
