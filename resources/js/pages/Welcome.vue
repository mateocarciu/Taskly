<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { dashboard, login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    ChevronsUpDown,
    FileText,
    Folder,
    History,
    KanbanSquare,
    LayoutGrid,
    Link as LinkIcon,
    ListCheck,
    MessageSquare,
    Tag,
    Tags,
} from '@lucide/vue';

const currentYear = new Date().getFullYear();

const features = [
    {
        icon: KanbanSquare,
        title: 'Visual Kanban Boards',
        description:
            'Organize work with custom columns, drag & drop simplicity, and clear status tracking.',
    },
    {
        icon: FileText,
        title: 'Rich Content & Files',
        description:
            'Write markdown descriptions and upload PDFs or images directly into your tasks.',
    },
    {
        icon: LinkIcon,
        title: 'Smart Link Previews',
        description:
            'Share URLs in task discussions and get automatic visual previews of the content.',
    },
    {
        icon: MessageSquare,
        title: 'Team Collaboration',
        description:
            'Keep conversations contextual with threaded comments, assignees, and task reporters.',
    },
    {
        icon: History,
        title: 'Activity Timeline',
        description:
            'Never miss a change. Every update, assignment, and comment is logged in a clear timeline.',
    },
    {
        icon: Tags,
        title: 'Tags & Filtering',
        description:
            'Use custom color-coded tags and powerful filters to find exactly what you need.',
    },
];

const boardColumns = [
    {
        title: 'To Do',
        count: 3,
        dot: 'bg-muted-foreground/40',
    },
    {
        title: 'In Progress',
        count: 2,
        dot: 'bg-primary',
    },
    {
        title: 'Done',
        count: 2,
        dot: 'bg-emerald-500',
    },
];
</script>

<template>
    <Head title="Welcome" />
    <div
        class="relative min-h-screen overflow-hidden bg-background text-foreground"
    >
        <div class="pointer-events-none absolute inset-0">
            <div
                class="absolute -top-40 left-1/2 h-[28rem] w-[28rem] -translate-x-1/2 rounded-full bg-primary/5 blur-3xl"
            />
            <div
                class="absolute inset-0 bg-[linear-gradient(to_right,var(--border)_1px,transparent_1px),linear-gradient(to_bottom,var(--border)_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,black,transparent)] bg-[size:56px_56px] opacity-40"
            />
        </div>

        <div
            class="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col px-6 py-6 md:px-8"
        >
            <header class="mb-14 flex items-center justify-between">
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
                        <Button as-child>
                            <Link :href="login()">Log in</Link>
                        </Button>
                    </template>
                </nav>
            </header>

            <main class="flex flex-1 flex-col gap-16">
                <section
                    class="mx-auto flex w-full max-w-3xl flex-col items-center text-center"
                >
                    <h1
                        class="bg-gradient-to-r from-foreground to-foreground/60 bg-clip-text text-4xl font-semibold tracking-tight text-balance text-transparent sm:text-6xl"
                    >
                        Plan less. Deliver more.
                    </h1>

                    <p
                        class="mt-5 max-w-2xl text-base text-pretty text-muted-foreground sm:text-lg"
                    >
                        Taskly keeps your team focused with visual boards,
                        powerful collaboration tools, and clear progress
                        tracking.
                    </p>

                    <div
                        class="mt-8 flex flex-wrap items-center justify-center gap-3"
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
                        </template>
                    </div>
                </section>

                <section class="mx-auto w-full max-w-6xl">
                    <div
                        class="overflow-hidden rounded-xl border bg-card shadow-sm"
                    >
                        <div
                            class="flex items-center gap-1.5 border-b bg-muted/40 px-4 py-3"
                        >
                            <span class="size-2.5 rounded-full bg-red-400/70" />
                            <span
                                class="size-2.5 rounded-full bg-amber-400/70"
                            />
                            <span
                                class="size-2.5 rounded-full bg-emerald-400/70"
                            />
                            <div class="mx-auto h-4 w-48 rounded-md bg-muted" />
                        </div>

                        <div class="flex">
                            <aside
                                class="hidden w-44 shrink-0 flex-col border-r bg-muted/20 sm:flex"
                            >
                                <div
                                    class="flex items-center gap-2 px-3 pt-3 pb-2"
                                >
                                    <AppLogoIcon class-name="h-5 w-5" />
                                    <div
                                        class="h-2 w-14 rounded-full bg-foreground/15"
                                    />
                                </div>

                                <div class="px-3 pb-2">
                                    <div
                                        class="flex items-center gap-1.5 rounded-md border bg-background px-2 py-1.5"
                                    >
                                        <div
                                            class="size-3 rounded bg-primary/15"
                                        />
                                        <div
                                            class="h-1.5 w-12 rounded-full bg-foreground/15"
                                        />
                                        <ChevronsUpDown
                                            class="ml-auto size-2.5 text-muted-foreground/60"
                                        />
                                    </div>
                                </div>

                                <nav class="flex flex-1 flex-col gap-0.5 px-2">
                                    <div
                                        class="flex items-center gap-2 rounded-md bg-accent px-2 py-1.5"
                                    >
                                        <LayoutGrid
                                            class="size-3 text-foreground/80"
                                        />
                                        <div
                                            class="h-1.5 w-10 rounded-full bg-foreground/20"
                                        />
                                    </div>
                                    <div
                                        class="flex items-center gap-2 px-2 py-1.5"
                                    >
                                        <ListCheck
                                            class="size-3 text-muted-foreground/60"
                                        />
                                        <div
                                            class="h-1.5 w-8 rounded-full bg-muted"
                                        />
                                    </div>
                                    <div
                                        class="flex items-center gap-2 px-2 py-1.5"
                                    >
                                        <Tag
                                            class="size-3 text-muted-foreground/60"
                                        />
                                        <div
                                            class="h-1.5 w-6 rounded-full bg-muted"
                                        />
                                    </div>
                                </nav>

                                <div
                                    class="flex items-center gap-2 border-t px-3 py-2.5"
                                >
                                    <div
                                        class="size-5 rounded-full bg-primary/10 ring-1 ring-border"
                                    />
                                    <div class="flex flex-col gap-1">
                                        <div
                                            class="h-1.5 w-12 rounded-full bg-foreground/15"
                                        />
                                        <div
                                            class="h-1 w-8 rounded-full bg-muted"
                                        />
                                    </div>
                                </div>
                            </aside>

                            <div class="grid flex-1 gap-3 p-4 sm:grid-cols-3">
                                <div
                                    v-for="column in boardColumns"
                                    :key="column.title"
                                    class="rounded-lg bg-muted/50 p-2.5"
                                >
                                    <div
                                        class="mb-2.5 flex items-center gap-1.5 px-1"
                                    >
                                        <span
                                            class="size-1.5 rounded-full"
                                            :class="column.dot"
                                        />
                                        <span
                                            class="text-[11px] font-medium text-muted-foreground"
                                        >
                                            {{ column.title }}
                                        </span>
                                        <span
                                            class="ml-auto rounded-full bg-background px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                                        >
                                            {{ column.count }}
                                        </span>
                                    </div>

                                    <div
                                        v-for="index in column.count"
                                        :key="index"
                                        class="mb-2 rounded-md border bg-background p-2.5 last:mb-0"
                                    >
                                        <div
                                            class="h-2 w-3/4 rounded-full bg-foreground/15"
                                        />
                                        <div
                                            class="mt-2 h-1.5 w-1/2 rounded-full bg-muted"
                                        />
                                        <div
                                            class="mt-2.5 flex items-center justify-between"
                                        >
                                            <div
                                                class="h-2 w-8 rounded-full bg-primary/20"
                                            />
                                            <div class="flex -space-x-1">
                                                <span
                                                    class="size-4 rounded-full bg-primary/10 ring-1 ring-border"
                                                />
                                                <span
                                                    class="size-4 rounded-full bg-muted ring-1 ring-border"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-x-12 gap-y-10 sm:grid-cols-2">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="flex gap-4"
                    >
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/5 text-primary ring-1 ring-primary/10 ring-inset"
                        >
                            <component :is="feature.icon" class="h-5 w-5" />
                        </div>
                        <div class="pt-0.5">
                            <h3 class="text-base font-semibold tracking-tight">
                                {{ feature.title }}
                            </h3>
                            <p
                                class="mt-1.5 text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ feature.description }}
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    class="relative overflow-hidden rounded-xl border bg-muted/40 px-6 py-12 text-center sm:px-12"
                >
                    <div
                        class="pointer-events-none absolute -top-24 left-1/2 h-48 w-96 -translate-x-1/2 rounded-full bg-primary/5 blur-3xl"
                    />
                    <h2
                        class="text-2xl font-semibold tracking-tight text-balance sm:text-3xl"
                    >
                        Ready to organize your team's work?
                    </h2>
                    <p
                        class="mx-auto mt-3 max-w-xl text-pretty text-muted-foreground"
                    >
                        No clutter, no noise, just what you need to plan,
                        execute, and follow through.
                    </p>
                    <Button
                        v-if="$page.props.auth.user"
                        size="lg"
                        as-child
                        class="mt-7"
                    >
                        <Link
                            :href="dashboard()"
                            class="inline-flex items-center gap-2"
                        >
                            Open dashboard
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button v-else size="lg" as-child class="mt-7">
                        <Link
                            :href="login()"
                            class="inline-flex items-center gap-2"
                        >
                            Get started
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </Button>
                </section>
            </main>

            <footer class="mt-14">
                <Separator class="mb-8" />
                <div
                    class="flex items-start justify-between gap-6 sm:flex-row sm:items-center"
                >
                    <div class="flex items-center gap-2.5">
                        <p class="text-sm text-muted-foreground">
                            &copy; {{ currentYear }} Taskly. All rights
                            reserved.
                        </p>
                    </div>
                    <a
                        href="https://github.com/mateocarciu/Taskly"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <Folder class="h-4 w-4" />
                        <span>Github Repo</span>
                    </a>
                </div>
            </footer>
        </div>
    </div>
</template>
