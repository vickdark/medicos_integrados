<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Ban, CircleCheck, Pencil, UserPlus } from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTableFilters } from '@/composables/useTableFilters';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate } from '@/lib/format';
import userRoutes from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import type { Option, Paginated, TableFilters } from '@/types/models';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: Option;
    profile: string | null;
    is_self: boolean;
    is_active: boolean;
    created_at: string;
};

const props = defineProps<{
    users: Paginated<UserRow>;
    filters: TableFilters;
    roles: Option[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Usuarios', href: userRoutes.index() },
];

const roleTones: Record<string, string> = {
    admin: 'bg-violet-100 text-violet-800 dark:bg-violet-500/15 dark:text-violet-300',
    receptionist:
        'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
    doctor: 'bg-teal-100 text-teal-800 dark:bg-teal-500/15 dark:text-teal-300',
    patient:
        'bg-neutral-200 text-neutral-700 dark:bg-neutral-500/20 dark:text-neutral-300',
};

const { filters, activeFilters, hasActiveFilters, reset } = useTableFilters(
    () => userRoutes.index().url,
    {
        search: props.filters.search,
        role: props.filters.role,
        status: props.filters.status,
    },
);

function toggleStatus(user: UserRow) {
    const action = user.is_active ? 'inactivar' : 'activar';

    if (!window.confirm(`¿Deseas ${action} el acceso de ${user.name}?`)) {
        return;
    }

    router.patch(userRoutes.status(user.id).url, {}, { preserveScroll: true });
}

const exportUrl = (format: 'xlsx' | 'pdf') =>
    userRoutes.export({ query: { ...activeFilters.value, format } }).url;
</script>

<template>
    <Head title="Usuarios" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight">Usuarios</h1>
                <Button as-child>
                    <Link :href="userRoutes.create()">
                        <UserPlus /> Nuevo usuario
                    </Link>
                </Button>
            </div>

            <TableToolbar
                v-model:search="filters.search"
                placeholder="Buscar por nombre o correo"
                :export-url="exportUrl"
                :can-reset="hasActiveFilters"
                @reset="reset"
            >
                <template #filters>
                    <div class="grid gap-1">
                        <Label
                            for="filter-role"
                            class="text-xs text-muted-foreground"
                            >Rol</Label
                        >
                        <NativeSelect
                            id="filter-role"
                            v-model="filters.role"
                            class="w-40"
                        >
                            <option value="">Todos</option>
                            <option
                                v-for="role in roles"
                                :key="role.value"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </option>
                        </NativeSelect>
                    </div>
                    <div class="grid gap-1">
                        <Label
                            for="filter-status"
                            class="text-xs text-muted-foreground"
                            >Acceso</Label
                        >
                        <NativeSelect
                            id="filter-status"
                            v-model="filters.status"
                            class="w-36"
                        >
                            <option value="">Todos</option>
                            <option value="active">Activos</option>
                            <option value="inactive">Inactivos</option>
                        </NativeSelect>
                    </div>
                </template>
            </TableToolbar>

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Usuario</th>
                            <th class="px-4 py-3 font-medium">Rol</th>
                            <th class="px-4 py-3 font-medium">Perfil</th>
                            <th class="px-4 py-3 font-medium">Acceso</th>
                            <th class="px-4 py-3 font-medium">Alta</th>
                            <th class="px-4 py-3 font-medium">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="props.users.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No se encontraron usuarios.
                            </td>
                        </tr>
                        <tr v-for="user in props.users.data" :key="user.id">
                            <td class="px-4 py-3">
                                <p class="font-medium">
                                    {{ user.name }}
                                    <span
                                        v-if="user.is_self"
                                        class="ml-1 text-xs font-normal text-muted-foreground"
                                        >(tú)</span
                                    >
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ user.email }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex w-fit items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap"
                                    :class="roleTones[user.role.value]"
                                >
                                    {{ user.role.label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ user.profile ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex w-fit items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap"
                                    :class="
                                        user.is_active
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300'
                                            : 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300'
                                    "
                                >
                                    {{ user.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        as-child
                                    >
                                        <Link :href="userRoutes.edit(user.id)">
                                            <Pencil /> Editar
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="!user.is_self"
                                        size="sm"
                                        variant="outline"
                                        @click="toggleStatus(user)"
                                    >
                                        <template v-if="user.is_active">
                                            <Ban /> Inactivar
                                        </template>
                                        <template v-else>
                                            <CircleCheck /> Activar
                                        </template>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :paginator="props.users" />
        </div>
    </AppLayout>
</template>
