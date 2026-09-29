<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Clock, UserPlus } from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMoney } from '@/lib/format';
import doctorRoutes from '@/routes/doctors';
import scheduleRoutes from '@/routes/schedules';
import type { BreadcrumbItem } from '@/types';
import type { Doctor, Paginated } from '@/types/models';

const props = defineProps<{
    doctors: Paginated<Doctor>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Médicos', href: doctorRoutes.index() },
];
</script>

<template>
    <Head title="Médicos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight">Médicos</h1>
                <Button as-child>
                    <Link :href="doctorRoutes.create()"
                        ><UserPlus /> Nuevo médico</Link
                    >
                </Button>
            </div>

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Médico</th>
                            <th class="px-4 py-3 font-medium">Especialidad</th>
                            <th class="px-4 py-3 font-medium">Colegiatura</th>
                            <th class="px-4 py-3 font-medium">Teléfono</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Tarifa
                            </th>
                            <th class="px-4 py-3 font-medium">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="doctor in props.doctors.data"
                            :key="doctor.id"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ doctor.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ doctor.email }}
                                </p>
                            </td>
                            <td class="px-4 py-3">{{ doctor.specialty }}</td>
                            <td class="px-4 py-3">
                                {{ doctor.license_number }}
                            </td>
                            <td class="px-4 py-3">{{ doctor.phone ?? '—' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ formatMoney(doctor.consultation_fee) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Button size="sm" variant="outline" as-child>
                                    <Link
                                        :href="scheduleRoutes.index(doctor.id)"
                                    >
                                        <Clock /> Horario
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :paginator="props.doctors" />
        </div>
    </AppLayout>
</template>
