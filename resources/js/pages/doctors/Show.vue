<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Clock, Pencil } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMoney } from '@/lib/format';
import doctorRoutes from '@/routes/doctors';
import scheduleRoutes from '@/routes/schedules';
import userRoutes from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import type { Doctor } from '@/types/models';

const props = defineProps<{
    doctor: Doctor;
    bio: string | null;
    schedule_summary: { days: string; ranges: string[] }[];
    stats: {
        appointments_month: number;
        upcoming: number;
        consultations: number;
        patients: number;
    };
    can: { edit: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Médicos', href: doctorRoutes.index() },
    { title: props.doctor.name, href: doctorRoutes.show(props.doctor.id) },
];

const initials = props.doctor.name
    .split(' ')
    .filter((part) => part.length > 0 && !/^(dr|dra)\.?$/i.test(part))
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase();

const details = [
    { label: 'Correo', value: props.doctor.email },
    { label: 'Teléfono', value: props.doctor.phone },
    { label: 'Colegiatura', value: props.doctor.license_number },
    {
        label: 'Tarifa de consulta',
        value: formatMoney(props.doctor.consultation_fee),
    },
    {
        label: 'Duración de cada cita',
        value: `${props.doctor.slot_minutes} minutos`,
    },
];

const figures = [
    { label: 'Citas este mes', value: props.stats.appointments_month },
    { label: 'Próximas citas', value: props.stats.upcoming },
    { label: 'Consultas realizadas', value: props.stats.consultations },
    { label: 'Pacientes atendidos', value: props.stats.patients },
];
</script>

<template>
    <Head :title="doctor.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div
                        class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border bg-brand-100 text-2xl font-semibold text-brand-800 dark:bg-brand-900 dark:text-brand-200"
                    >
                        <img
                            v-if="doctor.photo_url"
                            :src="doctor.photo_url"
                            :alt="`Foto de ${doctor.name}`"
                            class="size-full object-cover"
                        />
                        <span v-else aria-hidden="true">{{ initials }}</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">
                            {{ doctor.name }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ doctor.specialty }}
                        </p>
                        <span
                            class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="
                                doctor.is_active
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300'
                                    : 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300'
                            "
                        >
                            {{
                                doctor.is_active
                                    ? 'Acceso activo'
                                    : 'Acceso inactivo'
                            }}
                        </span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="doctorRoutes.index()">
                            <ArrowLeft /> Volver
                        </Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="scheduleRoutes.index(doctor.id)">
                            <Clock /> Horario
                        </Link>
                    </Button>
                    <Button v-if="can.edit" as-child>
                        <Link :href="userRoutes.edit(doctor.user_id)">
                            <Pencil /> Editar
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="figure in figures"
                    :key="figure.label"
                    class="rounded-xl border bg-card p-4"
                >
                    <p class="text-sm text-muted-foreground">
                        {{ figure.label }}
                    </p>
                    <p class="text-3xl font-semibold tracking-tight">
                        {{ figure.value }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Datos del médico</CardTitle>
                    </CardHeader>
                    <CardContent>
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
                                <dd
                                    class="col-span-2 break-words whitespace-pre-line"
                                >
                                    {{ bio || '—' }}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Horario de atención</CardTitle>
                        <CardDescription v-if="schedule_summary.length === 0">
                            Sin horario registrado: se aceptan citas en
                            cualquier horario.
                        </CardDescription>
                    </CardHeader>
                    <CardContent v-if="schedule_summary.length">
                        <ul class="divide-y rounded-lg border">
                            <li
                                v-for="row in schedule_summary"
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
                                        class="rounded-full border border-brand-300 bg-brand-50 px-2.5 py-0.5 text-xs font-medium whitespace-nowrap text-brand-800 tabular-nums dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-300"
                                        >{{ range }}</span
                                    >
                                </span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
