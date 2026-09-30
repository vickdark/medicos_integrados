<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import UserController from '@/actions/App/Http/Controllers/UserController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import PatientFormFields from '@/components/patients/PatientFormFields.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import type { DoctorProfile } from '@/components/users/DoctorFields.vue';
import DoctorFields from '@/components/users/DoctorFields.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import userRoutes from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import type { Option, Patient } from '@/types/models';

const props = defineProps<{
    user: { id: number; name: string; email: string; role: string } | null;
    doctor: DoctorProfile | null;
    patient: Patient | null;
    initialRole: string;
    roleOptions: Option[];
    specialties: { id: number; name: string }[];
    genders: Option[];
    bloodTypes: string[];
}>();

const isEditing = computed(() => props.user !== null);
const title = computed(() =>
    isEditing.value ? 'Editar usuario' : 'Nuevo usuario',
);

const role = ref(props.initialRole);
const isRoleLocked = computed(
    () => isEditing.value && props.roleOptions.length === 1,
);

const roleHints: Record<string, string> = {
    admin: 'Acceso total: usuarios, médicos, especialidades, pagos y auditoría.',
    receptionist:
        'Gestiona pacientes, citas y pagos. No ve la historia clínica.',
    doctor: 'Atiende pacientes, registra consultas y gestiona su horario.',
    patient: 'Accede a su historial, citas y pagos desde el portal.',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Usuarios', href: userRoutes.index() },
    {
        title: isEditing.value ? 'Editar' : 'Nuevo',
        href: props.user ? userRoutes.edit(props.user.id) : userRoutes.create(),
    },
];

const formAction = computed(() =>
    props.user
        ? UserController.update.form(props.user.id)
        : UserController.store.form(),
);
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    Elige el rol y completa aquí todos los datos de la cuenta;
                    no hará falta pasar por otro módulo.
                </p>
            </div>

            <Form
                v-bind="formAction"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <section class="space-y-4">
                    <h2
                        class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Rol
                    </h2>
                    <div class="grid gap-2">
                        <Label for="role">Tipo de usuario *</Label>
                        <template v-if="isRoleLocked">
                            <input type="hidden" name="role" :value="role" />
                            <p
                                class="rounded-md border bg-muted/40 px-3 py-2 text-sm"
                            >
                                {{ roleOptions[0].label }}
                                <span class="text-muted-foreground">
                                    · El rol de esta cuenta no se puede cambiar.
                                </span>
                            </p>
                        </template>
                        <NativeSelect
                            v-else
                            id="role"
                            v-model="role"
                            name="role"
                            required
                        >
                            <option value="" disabled>Selecciona un rol</option>
                            <option
                                v-for="option in roleOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </NativeSelect>
                        <p
                            v-if="roleHints[role]"
                            class="text-xs text-muted-foreground"
                        >
                            {{ roleHints[role] }}
                        </p>
                        <InputError :message="errors.role" />
                    </div>
                </section>

                <template v-if="role">
                    <section class="space-y-4">
                        <h2
                            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Cuenta de acceso
                        </h2>
                        <div v-if="role !== 'patient'" class="grid gap-2">
                            <Label for="name">Nombre completo *</Label>
                            <Input
                                id="name"
                                name="name"
                                :default-value="user?.name ?? ''"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="email">Correo electrónico *</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                :default-value="user?.email ?? ''"
                                autocomplete="off"
                                required
                            />
                            <p
                                v-if="role === 'patient' && !isEditing"
                                class="text-xs text-muted-foreground"
                            >
                                Si la clínica ya registró un paciente con este
                                correo, la cuenta se vinculará a esa ficha.
                            </p>
                            <InputError :message="errors.email" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="password">
                                    {{
                                        isEditing
                                            ? 'Nueva contraseña'
                                            : 'Contraseña temporal *'
                                    }}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    autocomplete="new-password"
                                    :required="!isEditing"
                                />
                                <p
                                    v-if="isEditing"
                                    class="text-xs text-muted-foreground"
                                >
                                    Déjala en blanco para conservar la actual.
                                    Si la cambias, será temporal.
                                </p>
                                <p
                                    v-if="!isEditing"
                                    class="text-xs text-muted-foreground"
                                >
                                    Temporal: el usuario deberá cambiarla al
                                    iniciar sesión.
                                </p>
                                <InputError :message="errors.password" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="password_confirmation">
                                    Confirmar contraseña
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    autocomplete="new-password"
                                    :required="!isEditing"
                                />
                            </div>
                        </div>
                        <div
                            class="flex items-start gap-3 rounded-md border bg-muted/30 p-3"
                        >
                            <Checkbox
                                id="send_credentials"
                                name="send_credentials"
                                :default-value="true"
                                class="mt-0.5"
                            />
                            <div class="grid gap-1">
                                <Label
                                    for="send_credentials"
                                    class="font-medium"
                                >
                                    {{
                                        isEditing
                                            ? 'Enviar la nueva contraseña por correo'
                                            : 'Enviar los datos de acceso por correo'
                                    }}
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        isEditing
                                            ? 'Solo se envía si escribes una nueva contraseña.'
                                            : 'El usuario recibirá su correo, la contraseña temporal y el enlace para iniciar sesión.'
                                    }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <DoctorFields
                        v-if="role === 'doctor'"
                        :errors="errors"
                        :specialties="specialties"
                        :doctor="doctor"
                    />

                    <PatientFormFields
                        v-if="role === 'patient'"
                        :errors="errors"
                        :genders="genders"
                        :blood-types="bloodTypes"
                        :patient="patient ?? undefined"
                        show-clinical-fields
                        hide-email
                    />
                </template>

                <div class="flex gap-2">
                    <Button :disabled="processing || !role">
                        {{ isEditing ? 'Guardar cambios' : 'Crear usuario' }}
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="userRoutes.index()">Cancelar</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
