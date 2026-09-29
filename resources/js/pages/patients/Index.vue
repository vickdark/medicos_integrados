<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Search, UserPlus } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import patientRoutes from '@/routes/patients';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, Patient } from '@/types/models';

const props = defineProps<{
    patients: Paginated<Patient>;
    filters: { search: string };
    can: { create: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pacientes', href: patientRoutes.index() },
];

const search = ref(props.filters.search);

const applySearch = useDebounceFn((term: string) => {
    router.get(patientRoutes.index().url, term ? { search: term } : {}, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(search, (term) => applySearch(term));
</script>

<template>
    <Head title="Pacientes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight">Pacientes</h1>
                <Button v-if="can.create" as-child>
                    <Link :href="patientRoutes.create()"
                        ><UserPlus /> Nuevo paciente</Link
                    >
                </Button>
            </div>

            <div class="relative max-w-sm">
                <Search
                    class="absolute top-2.5 left-3 size-4 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Buscar por nombre, documento o correo"
                    aria-label="Buscar pacientes"
                />
            </div>

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Paciente</th>
                            <th class="px-4 py-3 font-medium">Documento</th>
                            <th class="px-4 py-3 font-medium">Edad</th>
                            <th class="px-4 py-3 font-medium">Teléfono</th>
                            <th class="px-4 py-3 font-medium">Portal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="props.patients.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No se encontraron pacientes.
                            </td>
                        </tr>
                        <tr
                            v-for="patient in props.patients.data"
                            :key="patient.id"
                            class="transition-colors hover:bg-muted/40"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="patientRoutes.show(patient.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ patient.full_name }}
                                </Link>
                                <p class="text-xs text-muted-foreground">
                                    {{ patient.email ?? '—' }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                {{ patient.document_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    patient.age !== null
                                        ? `${patient.age} años`
                                        : '—'
                                }}
                            </td>
                            <td class="px-4 py-3">
                                {{ patient.phone ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="text-xs"
                                    :class="
                                        patient.has_account
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-muted-foreground'
                                    "
                                >
                                    {{
                                        patient.has_account
                                            ? 'Con cuenta'
                                            : 'Sin cuenta'
                                    }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :paginator="props.patients" />
        </div>
    </AppLayout>
</template>
