<script setup lang="ts">
import AuthCardLayout from '@/layouts/auth/AuthCardLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Hourglass, Mail, Sparkles, UserPlus } from 'lucide-vue-next';

const steps = [
    {
        icon: UserPlus,
        title: 'An admin adds you',
        text: 'You need to be added to a team before you can get in.',
    },
    {
        icon: Mail,
        title: 'Reach out once invited',
        text: 'Contact your administrator so they can set you up.',
    },
    {
        icon: Sparkles,
        title: 'Access unlocks instantly',
        text: 'You can start using the platform right away.',
    },
];
</script>

<template>
    <AuthCardLayout
        title="Account pending"
        description="Your access hasn't been activated yet"
    >
        <Head title="Account Pending" />

        <div class="flex flex-col gap-8">
            <div class="relative mx-auto size-16">
                <div
                    class="relative flex size-16 items-center justify-center rounded-2xl border bg-card shadow-sm"
                >
                    <Hourglass class="size-7 text-primary" />
                </div>
            </div>

            <p
                class="text-center text-sm leading-relaxed text-muted-foreground"
            >
                An administrator needs to add you to a team before you can
                access the platform.
            </p>

            <div class="space-y-5">
                <div
                    v-for="step in steps"
                    :key="step.title"
                    class="flex items-start gap-3"
                >
                    <div
                        class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-muted"
                    >
                        <component
                            :is="step.icon"
                            class="size-4 text-muted-foreground"
                        />
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-sm font-medium">{{ step.title }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ step.text }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthCardLayout>
</template>
