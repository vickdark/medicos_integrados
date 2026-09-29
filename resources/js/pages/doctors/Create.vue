<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import DoctorController from '@/actions/App/Http/Controllers/DoctorController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import doctorRoutes from '@/routes/doctors';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    specialties: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Médicos', href: doctorRoutes.index() },
    { title: 'Nuevo médico', href: doctorRoutes.create() },
];
</script>

<template>
    <Head title="Nuevo médico" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4">
            <h1 class="text-2xl font-semibold tracking-tight">Nuevo médico</h1>

            <Form
                v-bind="DoctorController.store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <section class="space-y-4">
                    <h2
                        class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Cuenta de acceso
                    </h2>
                    <div class="grid gap-2">
                        <Label for="name">Nombre completo *</Label>
                        <Input
                            id="name"
                            name="name"
                            placeholder="Dr. Juan Pérez"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Correo electrónico *</Label>
                        <Input id="email" type="email" name="email" required />
                        <InputError :message="errors.email" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="password">Contraseña temporal *</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                autocomplete="new-password"
                                required
                            />
                            <InputError :message="errors.password" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="password_confirmation"
                                >Confirmar contraseña *</Label
                            >
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                autocomplete="new-password"
                                required
                            />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <h2
                        class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Datos profesionales
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="specialty_id">Especialidad *</Label>
                            <NativeSelect
                                id="specialty_id"
                                name="specialty_id"
                                default-value=""
                                required
                            >
                                <option value="" disabled>
                                    Selecciona una especialidad
                                </option>
                                <option
                                    v-for="specialty in specialties"
                                    :key="specialty.id"
                                    :value="specialty.id"
                                >
                                    {{ specialty.name }}
                                </option>
                            </NativeSelect>
                            <InputError :message="errors.specialty_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="license_number"
                                >N.º de colegiatura *</Label
                            >
                            <Input
                                id="license_number"
                                name="license_number"
                                required
                            />
                            <InputError :message="errors.license_number" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="phone">Teléfono</Label>
                            <Input id="phone" name="phone" />
                            <InputError :message="errors.phone" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="consultation_fee"
                                >Tarifa de consulta *</Label
                            >
                            <Input
                                id="consultation_fee"
                                type="number"
                                name="consultation_fee"
                                step="0.01"
                                min="0"
                                default-value="0"
                                required
                            />
                            <InputError :message="errors.consultation_fee" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="bio">Reseña profesional</Label>
                        <Textarea id="bio" name="bio" />
                        <InputError :message="errors.bio" />
                    </div>
                </section>

                <div class="flex gap-2">
                    <Button :disabled="processing">Registrar médico</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="doctorRoutes.index()">Cancelar</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
