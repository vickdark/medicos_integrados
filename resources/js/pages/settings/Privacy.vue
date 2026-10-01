<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrivacySettingsController from '@/actions/App/Http/Controllers/Settings/PrivacySettingsController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { formatDate } from '@/lib/format';
import { privacy } from '@/routes';
import { edit } from '@/routes/privacy-settings';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    policy: {
        version: string;
        updated_at: string;
        company: string;
        nit: string | null;
        address: string | null;
        phone: string | null;
        contact_email: string;
        rnbd_registration: string | null;
    };
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Política de datos', href: edit() },
];

const confirmingPublish = ref(false);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Política de datos" />

        <h1 class="sr-only">Política de datos</h1>

        <SettingsLayout>
            <div class="space-y-8">
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Responsable del tratamiento"
                        description="Estos datos se publican en la política de tratamiento de datos personales. Solo se muestran los que completes."
                    />

                    <Form
                        v-bind="PrivacySettingsController.update.form()"
                        :options="{ preserveScroll: true }"
                        class="space-y-4"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="company">Razón social</Label>
                            <Input
                                id="company"
                                name="company"
                                :default-value="policy.company"
                                required
                            />
                            <InputError :message="errors.company" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="nit">NIT</Label>
                            <Input
                                id="nit"
                                name="nit"
                                :default-value="policy.nit ?? ''"
                            />
                            <InputError :message="errors.nit" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="address">Dirección</Label>
                            <Input
                                id="address"
                                name="address"
                                :default-value="policy.address ?? ''"
                            />
                            <InputError :message="errors.address" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="phone">Teléfono</Label>
                            <Input
                                id="phone"
                                name="phone"
                                :default-value="policy.phone ?? ''"
                            />
                            <InputError :message="errors.phone" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="contact_email"
                                >Correo para solicitudes sobre datos
                                personales</Label
                            >
                            <Input
                                id="contact_email"
                                type="email"
                                name="contact_email"
                                :default-value="policy.contact_email"
                                required
                            />
                            <InputError :message="errors.contact_email" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="rnbd_registration"
                                >Registro en el RNBD (opcional)</Label
                            >
                            <Input
                                id="rnbd_registration"
                                name="rnbd_registration"
                                :default-value="policy.rnbd_registration ?? ''"
                            />
                            <p class="text-xs text-muted-foreground">
                                Verifica con tu asesor legal si la clínica debe
                                inscribir sus bases de datos en el Registro
                                Nacional de Bases de Datos de la SIC. Si ya la
                                inscribió, escribe aquí el número.
                            </p>
                            <InputError :message="errors.rnbd_registration" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button :disabled="processing">Guardar</Button>
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Guardado.
                            </p>
                        </div>
                    </Form>
                </div>

                <div class="space-y-4 border-t pt-8">
                    <Heading
                        variant="small"
                        title="Versión de la política"
                        :description="`Versión ${policy.version}, actualizada el ${formatDate(policy.updated_at)}`"
                    />

                    <p class="text-sm text-muted-foreground">
                        Cambiar los datos del responsable no exige una nueva
                        aceptación. Publica una nueva versión solo cuando el
                        texto de fondo de la
                        <Link
                            :href="privacy()"
                            class="underline underline-offset-4"
                            >política</Link
                        >
                        cambie y tu asesor legal lo revise: todos los pacientes
                        deberán aceptarla de nuevo para seguir usando el
                        sistema.
                    </p>

                    <Button
                        v-if="!confirmingPublish"
                        variant="outline"
                        @click="confirmingPublish = true"
                    >
                        Publicar nueva versión
                    </Button>

                    <Form
                        v-else
                        v-bind="PrivacySettingsController.publish.form()"
                        :options="{ preserveScroll: true }"
                        class="space-y-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200"
                        v-slot="{ processing }"
                        @success="confirmingPublish = false"
                    >
                        <p>
                            Se publicará la siguiente versión y los pacientes
                            tendrán que aceptarla en su próximo ingreso. ¿Quieres
                            continuar?
                        </p>
                        <div class="flex gap-2">
                            <Button :disabled="processing">Sí, publicar</Button>
                            <Button
                                type="button"
                                variant="ghost"
                                @click="confirmingPublish = false"
                                >Cancelar</Button
                            >
                        </div>
                    </Form>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
