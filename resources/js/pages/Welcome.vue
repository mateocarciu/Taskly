<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    ListChecks,
    Sparkles,
    Users2,
} from 'lucide-vue-next';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const features = [
    {
        icon: Users2,
        title: 'Team Workspace',
        description:
            'Collaborate with your team in one organized workspace and stay aligned.',
    },
    {
        icon: ListChecks,
        title: 'Task Tracking',
        description:
            'Create, assign, and track progress with a clean workflow built for focus.',
    },
    {
        icon: CheckCircle2,
        title: 'Clear Progress',
        description:
            'Understand what is done, what is next, and where your team needs support.',
    },
];
</script>

<template>
    <Head title="Welcome" />
    <div
        class="relative min-h-screen overflow-hidden bg-background text-foreground"
    >
        <div
            class="pointer-events-none absolute -top-28 left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-slate-300/30 blur-3xl dark:bg-slate-700/30"
        />

        <div
            class="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col px-6 py-6 md:px-8"
        >
            <header class="mb-10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg p-2">
                        <AppLogoIcon class-name="h-7 w-7" />
                    </div>
                    <span class="text-md font-semibold tracking-wide"
                        >Taskly</span
                    >
                </div>

                <nav class="flex items-center gap-2">
                    <Button
                        v-if="$page.props.auth.user"
                        variant="outline"
                        as-child
                    >
                        <Link :href="dashboard()">Dashboard</Link>
                    </Button>
                    <template v-else>
                        <Button variant="ghost" as-child>
                            <Link :href="login()">Sign in</Link>
                        </Button>
                        <Button v-if="canRegister" as-child>
                            <Link :href="register()">Create account</Link>
                        </Button>
                    </template>
                </nav>
            </header>

            <main class="flex flex-1 flex-col gap-10">
                <section
                    class="mx-auto flex w-full max-w-3xl flex-col items-center text-center"
                >
                    <Badge variant="secondary" class="mb-5 gap-1.5 px-3 py-1">
                        <Sparkles class="h-3.5 w-3.5" />
                        Team productivity made clear
                    </Badge>

                    <h1
                        class="text-4xl font-semibold tracking-tight text-balance sm:text-5xl"
                    >
                        Plan less. Deliver more.
                    </h1>

                    <p
                        class="mt-4 max-w-2xl text-base text-pretty text-muted-foreground sm:text-lg"
                    >
                        Taskly keeps your team focused with simple task flows,
                        clear ownership, and real progress visibility.
                    </p>

                    <div
                        class="mt-7 flex flex-wrap items-center justify-center gap-3"
                    >
                        <Button v-if="$page.props.auth.user" size="lg" as-child>
                            <Link
                                :href="dashboard()"
                                class="inline-flex items-center gap-2"
                            >
                                Open dashboard
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </Button>

                        <template v-else>
                            <Button size="lg" as-child>
                                <Link
                                    :href="login()"
                                    class="inline-flex items-center gap-2"
                                >
                                    Get started
                                    <ArrowRight class="h-4 w-4" />
                                </Link>
                            </Button>
                            <Button
                                v-if="canRegister"
                                size="lg"
                                variant="outline"
                                as-child
                            >
                                <Link :href="register()"
                                    >Create free account</Link
                                >
                            </Button>
                        </template>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-3">
                    <Card
                        v-for="feature in features"
                        :key="feature.title"
                        class="border-border/70 bg-card/70 backdrop-blur-sm"
                    >
                        <CardHeader>
                            <div
                                class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg border bg-background"
                            >
                                <component
                                    :is="feature.icon"
                                    class="h-5 w-5 text-muted-foreground"
                                />
                            </div>
                            <CardTitle>{{ feature.title }}</CardTitle>
                            <CardDescription>{{
                                feature.description
                            }}</CardDescription>
                        </CardHeader>
                        <CardContent />
                    </Card>
                </section>

                <Card class="border-dashed bg-muted/30">
                    <CardHeader class="gap-2">
                        <CardTitle class="text-lg"
                            >Built for fast-moving teams</CardTitle
                        >
                        <CardDescription>
                            No clutter, no noise, just what you need to plan,
                            execute, and follow through.
                        </CardDescription>
                    </CardHeader>
                </Card>
            </main>

            <footer class="mt-10">
                <Separator class="mb-4" />
                <div
                    class="flex flex-col items-center justify-between gap-2 text-center text-sm text-muted-foreground sm:flex-row sm:text-left"
                >
                    <p>Taskly</p>
                    <p>Simple task management for modern teams.</p>
                </div>
            </footer>
        </div>
    </div>
</template>
