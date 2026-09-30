<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Clock } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { formatMoney } from '@/lib/format';
import { show } from '@/routes/doctor-profile';
import scheduleRoutes from '@/routes/schedules';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    profile: {
        id: number;
        name: string;
        email: string;
        specialty: string;
        license_number: string;
        phone: string | null;
        consultation_fee: string;
        slot_minutes: number;
        bio: string | null;
        schedule_summary: { days: string; ranges: string[] }[];
    };
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Perfil profesional', href: show() },
];

const details = [
    { label: 'Nombre', value: props.profile.name },
    { label: 'Correo', value: props.profile.email },
    { label: 'Especialidad', value: props.profile.specialty },
    { label: 'Colegiatura', value: props.profile.license_number },
    { label: 'Teléfono', value: props.profile.phone },
    {
        label: 'Tarifa de consulta',
        value: formatMoney(props.profile.consultation_fee),
    },
    {
        label: 'Duración de cada cita',
        value: `${props.profile.slot_minutes} minutos`,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Perfil profesional" />

        <h1 class="sr-only">Perfil profesional</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Perfil profesional"
                    description="Tus datos como médico en la clínica. Si algo debe corregirse, pídelo al administrador."
                />

                <dl class="grid gap-3 text-sm">
                    <div
                        v-for="detail in details"
                        :key="detail.label"
                        class="grid grid-cols-3 gap-2"
                    >
                        <dt class="text-muted-foreground">
                            {{ detail.label }}
                        </dt>
                        <dd class="col-span-2 break-words">
                            {{ detail.value || '—' }}
                        </dd>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <dt class="text-muted-foreground">Reseña</dt>
                        <dd class="col-span-2 break-words whitespace-pre-line">
                            {{ profile.bio || '—' }}
                        </dd>
                    </div>
                </dl>

                <div class="grid gap-3">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold">
                            Horario de atención
                        </h3>
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="scheduleRoutes.index(profile.id)">
                                <Clock /> Administrar horario
                            </Link>
                        </Button>
                    </div>
                    <ul
                        v-if="profile.schedule_summary.length"
                        class="divide-y rounded-lg border"
                    >
                        <li
                            v-for="row in profile.schedule_summary"
                            :key="row.days"
                            class="flex flex-wrap items-center gap-x-4 gap-y-1.5 px-4 py-2.5 text-sm"
                        >
                            <span class="w-24 shrink-0 font-medium">{{
                                row.days
                            }}</span>
                            <span class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="range in row.ranges"
                                    :key="range"
                                    class="rounded-full border border-emerald-300 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium whitespace-nowrap text-emerald-800 tabular-nums dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300"
                                    >{{ range }}</span
                                >
                            </span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        Aún no tienes un horario registrado.
                    </p>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
