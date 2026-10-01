<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import PersonalDataController from '@/actions/App/Http/Controllers/Settings/PersonalDataController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { formatDate } from '@/lib/format';
import { privacy } from '@/routes';
import { edit } from '@/routes/personal-data';
import type { BreadcrumbItem } from '@/types';
import type { Option } from '@/types/models';

type DataRequest = {
    id: number;
    type: Option;
    details: string;
    status: Option;
    is_overdue: boolean;
    due_at: string;
    response: string | null;
    responded_at: string | null;
    created_at: string;
};

defineProps<{
    policy: { version: string | null; accepted_at: string | null };
    types: (Option & { days: number })[];
    requests: DataRequest[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Mis datos personales', href: edit() },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Mis datos personales" />

        <h1 class="sr-only">Mis datos personales</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Tus derechos sobre tus datos"
                    description="Puedes conocer, corregir o pedir la supresión de tus datos personales (Ley 1581 de 2012)"
                />

                <p class="text-sm text-muted-foreground">
                    <template v-if="policy.accepted_at">
                        Aceptaste la
                        <a
                            :href="privacy().url"
                            target="_blank"
                            rel="noopener"
                            class="underline underline-offset-4"
                            >política de tratamiento de datos</a
                        >
                        (versión {{ policy.version }}) el
                        {{ formatDate(policy.accepted_at) }}.
                    </template>
                </p>

                <div class="space-y-2 rounded-lg border p-4">
                    <h3 class="text-sm font-medium">Descargar mis datos</h3>
                    <p class="text-sm text-muted-foreground">
                        Obtén una copia en formato JSON de tus datos personales,
                        citas, consultas y pagos. Te pediremos tu contraseña.
                    </p>
                    <Button variant="outline" as-child>
                        <a :href="PersonalDataController.download().url">
                            <Download /> Descargar mis datos
                        </a>
                    </Button>
                </div>
            </div>

            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Hacer una solicitud"
                    description="La clínica debe responderte dentro de los plazos de ley (días hábiles)"
                />

                <Form
                    v-bind="PersonalDataController.store.form()"
                    class="space-y-4"
                    reset-on-success
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="type">¿Qué quieres solicitar?</Label>
                        <NativeSelect id="type" name="type" required>
                            <option value="">Selecciona una opción</option>
                            <option
                                v-for="type in types"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }} ({{ type.days }} días hábiles)
                            </option>
                        </NativeSelect>
                        <InputError :message="errors.type" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="details">Cuéntanos tu solicitud</Label>
                        <Textarea id="details" name="details" rows="4" />
                        <InputError :message="errors.details" />
                    </div>

                    <p class="text-xs text-muted-foreground">
                        La historia clínica debe conservarse por ley, por lo que
                        la supresión aplica a los datos que no hacen parte de
                        ella.
                    </p>

                    <Button :disabled="processing">Enviar solicitud</Button>
                </Form>
            </div>

            <div class="space-y-4">
                <Heading variant="small" title="Mis solicitudes" />

                <p
                    v-if="requests.length === 0"
                    class="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                >
                    Aún no has hecho solicitudes.
                </p>

                <ul v-else class="space-y-3">
                    <li
                        v-for="request in requests"
                        :key="request.id"
                        class="space-y-2 rounded-lg border p-4 text-sm"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <span class="font-medium">{{
                                request.type.label
                            }}</span>
                            <StatusBadge :status="request.status" />
                        </div>
                        <p class="text-muted-foreground">
                            {{ request.details }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Solicitada el {{ formatDate(request.created_at) }} ·
                            Plazo de respuesta:
                            {{ formatDate(request.due_at) }}
                            <span
                                v-if="request.is_overdue"
                                class="font-medium text-destructive"
                                >(vencido)</span
                            >
                        </p>
                        <div
                            v-if="request.response"
                            class="rounded-md bg-muted p-3"
                        >
                            <p class="text-xs text-muted-foreground">
                                Respuesta del
                                {{ formatDate(request.responded_at) }}
                            </p>
                            <p>{{ request.response }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
