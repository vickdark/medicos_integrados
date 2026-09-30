<script setup lang="ts">
import { ImagePlus } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
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
    photo_url?: string | null;
};

const slotOptions = [10, 15, 20, 30, 45, 60];

defineProps<{
    errors: Record<string, string>;
    specialties: { id: number; name: string }[];
    doctor?: DoctorProfile | null;
}>();

const preview = ref<string | null>(null);

function onPhotoChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    preview.value = file ? URL.createObjectURL(file) : null;
}
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
                <SearchableSelect
                    id="specialty_id"
                    name="specialty_id"
                    :options="
                        specialties.map((specialty) => ({
                            value: specialty.id,
                            label: specialty.name,
                        }))
                    "
                    :default-value="doctor?.specialty_id ?? ''"
                    placeholder="Selecciona una especialidad"
                    search-placeholder="Buscar especialidad"
                    required
                />
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
            <Label for="photo">Foto de perfil</Label>
            <div class="flex items-center gap-4">
                <div
                    class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border bg-muted text-muted-foreground"
                >
                    <img
                        v-if="preview || doctor?.photo_url"
                        :src="preview ?? doctor?.photo_url ?? ''"
                        alt="Foto del médico"
                        class="size-full object-cover"
                    />
                    <ImagePlus v-else class="size-6" />
                </div>
                <div class="grid gap-2">
                    <Input
                        id="photo"
                        type="file"
                        name="photo"
                        accept="image/jpeg,image/png,image/webp"
                        @change="onPhotoChange"
                    />
                    <p class="text-xs text-muted-foreground">
                        JPG, PNG o WebP de hasta 2 MB. Se muestra en su perfil
                        y, si se activa, en la página de inicio.
                    </p>
                    <label
                        v-if="doctor?.photo_url"
                        class="flex items-center gap-2 text-sm"
                    >
                        <input type="checkbox" name="remove_photo" value="1" />
                        Quitar la foto actual
                    </label>
                </div>
            </div>
            <InputError :message="errors.photo" />
        </div>
        <div class="grid gap-2">
            <Label for="bio">Reseña profesional</Label>
            <Textarea id="bio" name="bio" :default-value="doctor?.bio ?? ''" />
            <InputError :message="errors.bio" />
        </div>
    </section>
</template>
