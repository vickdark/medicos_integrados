<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { Option, Patient } from '@/types/models';

defineProps<{
    errors: Record<string, string>;
    genders: Option[];
    bloodTypes: string[];
    patient?: Patient;
    showClinicalFields?: boolean;
    contactOnly?: boolean;
}>();
</script>

<template>
    <section v-if="!contactOnly" class="space-y-4">
        <h2
            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
        >
            Datos personales
        </h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="first_name">Nombres *</Label>
                <Input
                    id="first_name"
                    name="first_name"
                    :default-value="patient?.first_name"
                    required
                />
                <InputError :message="errors.first_name" />
            </div>
            <div class="grid gap-2">
                <Label for="last_name">Apellidos *</Label>
                <Input
                    id="last_name"
                    name="last_name"
                    :default-value="patient?.last_name"
                    required
                />
                <InputError :message="errors.last_name" />
            </div>
            <div class="grid gap-2">
                <Label for="document_number">Documento de identidad</Label>
                <Input
                    id="document_number"
                    name="document_number"
                    :default-value="patient?.document_number ?? ''"
                />
                <InputError :message="errors.document_number" />
            </div>
            <div class="grid gap-2">
                <Label for="birth_date">Fecha de nacimiento</Label>
                <Input
                    id="birth_date"
                    type="date"
                    name="birth_date"
                    :default-value="patient?.birth_date ?? ''"
                />
                <InputError :message="errors.birth_date" />
            </div>
            <div class="grid gap-2">
                <Label for="gender">Sexo</Label>
                <NativeSelect
                    id="gender"
                    name="gender"
                    :default-value="patient?.gender?.value ?? ''"
                >
                    <option value="">Sin especificar</option>
                    <option
                        v-for="gender in genders"
                        :key="gender.value"
                        :value="gender.value"
                    >
                        {{ gender.label }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.gender" />
            </div>
            <div class="grid gap-2">
                <Label for="blood_type">Grupo sanguíneo</Label>
                <NativeSelect
                    id="blood_type"
                    name="blood_type"
                    :default-value="patient?.blood_type ?? ''"
                >
                    <option value="">Sin especificar</option>
                    <option
                        v-for="bloodType in bloodTypes"
                        :key="bloodType"
                        :value="bloodType"
                    >
                        {{ bloodType }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.blood_type" />
            </div>
        </div>
    </section>

    <section class="space-y-4">
        <h2
            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
        >
            Contacto
        </h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div v-if="!contactOnly" class="grid gap-2">
                <Label for="email">Correo electrónico</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    :default-value="patient?.email ?? ''"
                />
                <p class="text-xs text-muted-foreground">
                    Si el paciente se registra con este correo, verá su
                    historial en el portal.
                </p>
                <InputError :message="errors.email" />
            </div>
            <div class="grid gap-2">
                <Label for="phone">Teléfono</Label>
                <Input
                    id="phone"
                    name="phone"
                    :default-value="patient?.phone ?? ''"
                />
                <InputError :message="errors.phone" />
            </div>
            <div class="grid gap-2 sm:col-span-2">
                <Label for="address">Dirección</Label>
                <Input
                    id="address"
                    name="address"
                    :default-value="patient?.address ?? ''"
                />
                <InputError :message="errors.address" />
            </div>
            <div class="grid gap-2">
                <Label for="emergency_contact_name"
                    >Contacto de emergencia</Label
                >
                <Input
                    id="emergency_contact_name"
                    name="emergency_contact_name"
                    :default-value="patient?.emergency_contact_name ?? ''"
                />
                <InputError :message="errors.emergency_contact_name" />
            </div>
            <div class="grid gap-2">
                <Label for="emergency_contact_phone"
                    >Teléfono de emergencia</Label
                >
                <Input
                    id="emergency_contact_phone"
                    name="emergency_contact_phone"
                    :default-value="patient?.emergency_contact_phone ?? ''"
                />
                <InputError :message="errors.emergency_contact_phone" />
            </div>
        </div>
    </section>

    <section v-if="showClinicalFields && !contactOnly" class="space-y-4">
        <h2
            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
        >
            Antecedentes médicos
        </h2>
        <div class="grid gap-4">
            <div class="grid gap-2">
                <Label for="allergies">Alergias</Label>
                <Textarea
                    id="allergies"
                    name="allergies"
                    :default-value="patient?.allergies ?? ''"
                />
                <InputError :message="errors.allergies" />
            </div>
            <div class="grid gap-2">
                <Label for="chronic_conditions">Enfermedades crónicas</Label>
                <Textarea
                    id="chronic_conditions"
                    name="chronic_conditions"
                    :default-value="patient?.chronic_conditions ?? ''"
                />
                <InputError :message="errors.chronic_conditions" />
            </div>
            <div class="grid gap-2">
                <Label for="medical_background"
                    >Antecedentes (quirúrgicos, familiares, etc.)</Label
                >
                <Textarea
                    id="medical_background"
                    name="medical_background"
                    :default-value="patient?.medical_background ?? ''"
                />
                <InputError :message="errors.medical_background" />
            </div>
        </div>
    </section>
</template>
