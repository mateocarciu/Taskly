<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, ListCheck, Users } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Root Me PRO - Task Manager">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]"
    >
        <header
            class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl"
        >
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>
        <div
            class="flex w-full items-center justify-center opacity-100 transition-opacity duration-500 lg:grow"
        >
            <main class="flex w-full max-w-4xl flex-col items-center gap-12">
                <div class="flex flex-col items-center gap-6 text-center">
                    <div
                        class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gray-900 shadow-xl dark:bg-gray-800"
                    >
                        <img
                            src="https://static.root-me.pro/svg/rmp_vertical_light.svg"
                            alt="Root Me PRO"
                            class="h-12 w-12"
                        />
                    </div>
                    <div>
                        <h1
                            class="mb-3 text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-100"
                        >
                            Root Me PRO Task Manager
                        </h1>
                    </div>
                </div>

                <div class="grid w-full gap-6 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <div
                                class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30"
                            >
                                <Users
                                    class="h-5 w-5 text-blue-600 dark:text-blue-400"
                                />
                            </div>
                            <CardTitle>Travail en équipe</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                Gérez vos tâches de manière collaborative au
                                sein de votre équipe
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div
                                class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30"
                            >
                                <ListCheck
                                    class="h-5 w-5 text-green-600 dark:text-green-400"
                                />
                            </div>
                            <CardTitle>Suivi des tâches</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                Créez, modifiez et suivez l'avancement de vos
                                tâches en temps réel
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div
                                class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30"
                            >
                                <CheckCircle
                                    class="h-5 w-5 text-purple-600 dark:text-purple-400"
                                />
                            </div>
                            <CardTitle>Statistiques</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                Visualisez les performances de votre équipe avec
                                des statistiques détaillées
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-4">
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="login()"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-8 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        Get Started
                    </Link>
                </div>
            </main>
        </div>
    </div>
</template>
