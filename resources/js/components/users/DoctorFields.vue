<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';

export type DoctorProfile = {
    specialty_id: number;
    license_number: string;
    phone: string | null;
    consultation_fee: string;
    slot_minutes: number;
    bio: string | null;
};

const slotOptions = [10, 15, 20, 30, 45, 60];

defineProps<{
    errors: Record<string, string>;
    specialties: { id: number; name: string }[];
    doctor?: DoctorProfile | null;
}>();
</script>

<template>
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
                    :default-value="doctor?.specialty_id ?? ''"
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
                <Label for="license_number">N.º de colegiatura *</Label>
                <Input
                    id="license_number"
                    name="license_number"
                    :default-value="doctor?.license_number ?? ''"
                    required
                />
                <InputError :message="errors.license_number" />
            </div>
            <div class="grid gap-2">
                <Label for="phone">Teléfono</Label>
                <Input
                    id="phone"
                    name="phone"
                    :default-value="doctor?.phone ?? ''"
                />
                <InputError :message="errors.phone" />
            </div>
            <div class="grid gap-2">
                <Label for="consultation_fee">Tarifa de consulta *</Label>
                <Input
                    id="consultation_fee"
                    type="number"
                    name="consultation_fee"
                    step="0.01"
                    min="0"
                    :default-value="doctor?.consultation_fee ?? '0'"
                    required
                />
                <InputError :message="errors.consultation_fee" />
            </div>
            <div class="grid gap-2">
                <Label for="slot_minutes">Duración de cada cita *</Label>
                <NativeSelect
                    id="slot_minutes"
                    name="slot_minutes"
                    :default-value="doctor?.slot_minutes ?? 30"
                    required
                >
                    <option
                        v-for="minutes in slotOptions"
                        :key="minutes"
                        :value="minutes"
                    >
                        {{ minutes }} minutos
                    </option>
                </NativeSelect>
                <InputError :message="errors.slot_minutes" />
            </div>
        </div>
        <div class="grid gap-2">
            <Label for="bio">Reseña profesional</Label>
            <Textarea id="bio" name="bio" :default-value="doctor?.bio ?? ''" />
            <InputError :message="errors.bio" />
        </div>
    </section>
</template>
