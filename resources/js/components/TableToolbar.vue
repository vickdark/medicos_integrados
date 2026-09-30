<script setup lang="ts">
import { FileSpreadsheet, FileText, Search, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineProps<{
    placeholder?: string;
    exportUrl?: (format: 'xlsx' | 'pdf') => string;
    canReset?: boolean;
}>();

const search = defineModel<string>('search', { default: '' });

const emit = defineEmits<{
    reset: [];
}>();
</script>

<template>
    <div
        class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"
    >
        <div class="flex flex-1 flex-wrap items-end gap-3">
            <div class="relative w-full sm:w-72">
                <Search
                    class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    class="pl-9"
                    :placeholder="placeholder ?? 'Buscar…'"
                    :aria-label="placeholder ?? 'Buscar'"
                />
            </div>

            <slot name="filters" />

            <Button
                v-if="canReset"
                variant="ghost"
                size="sm"
                class="text-muted-foreground"
                @click="emit('reset')"
            >
                <X /> Limpiar filtros
            </Button>
        </div>

        <div v-if="exportUrl" class="flex gap-2">
            <Button variant="outline" size="sm" as-child>
                <a :href="exportUrl('xlsx')" data-test="export-xlsx">
                    <FileSpreadsheet class="text-emerald-600" /> Excel
                </a>
            </Button>
            <Button variant="outline" size="sm" as-child>
                <a :href="exportUrl('pdf')" data-test="export-pdf">
                    <FileText class="text-red-600" /> PDF
                </a>
            </Button>
        </div>
    </div>
</template>
