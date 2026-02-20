<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { type Team } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    teams: Team[];
}>();

const form = useForm({
    team_id: '',
});

const submit = () => {
    form.post('/teams/join');
};
</script>

<template>
    <AuthLayout
        title="Join a team"
        description="Select a team to join before continuing"
    >
        <Head title="Select Team" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="team">Team</Label>
                    <select
                        id="team"
                        v-model="form.team_id"
                        name="team_id"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                    >
                        <option value="" disabled>Select a team</option>
                        <option
                            v-for="team in teams"
                            :key="team.id"
                            :value="team.id"
                        >
                            {{ team.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.team_id" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    :disabled="form.processing || !form.team_id"
                >
                    <Spinner v-if="form.processing" />
                    Join team
                </Button>
            </div>
        </form>
    </AuthLayout>
</template>
