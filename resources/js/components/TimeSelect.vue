<script setup lang="ts">
import { NativeSelect } from '@/components/ui/native-select';
import { formatClock } from '@/lib/format';

/**
 * Time picker that shows a 12-hour clock but submits a 24-hour "HH:mm" value,
 * which is what the server validates.
 */
const props = withDefaults(
    defineProps<{
        id?: string;
        name?: string;
        modelValue?: string;
        defaultValue?: string;
        stepMinutes?: number;
        required?: boolean;
    }>(),
    { stepMinutes: 15 },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const options: { value: string; label: string }[] = [];

for (let minutes = 0; minutes < 24 * 60; minutes += props.stepMinutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    options.push({
        value: `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`,
        label: formatClock(hours, mins),
    });
}
</script>

<template>
    <NativeSelect
        :id="id"
        :name="name"
        :model-value="modelValue"
        :default-value="defaultValue ?? ''"
        :required="required"
        @update:model-value="emit('update:modelValue', String($event ?? ''))"
    >
        <option value="" disabled>Selecciona la hora</option>
        <option
            v-for="option in options"
            :key="option.value"
            :value="option.value"
        >
            {{ option.label }}
        </option>
    </NativeSelect>
</template>
