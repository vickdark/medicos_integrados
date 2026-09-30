<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const from = defineModel<string>('from', { default: '' });
const to = defineModel<string>('to', { default: '' });
</script>

<template>
    <div class="flex items-end gap-2">
        <div class="grid gap-1">
            <Label for="filter-from" class="text-xs text-muted-foreground"
                >Desde</Label
            >
            <Input
                id="filter-from"
                v-model="from"
                type="date"
                class="w-40"
                :max="to || undefined"
            />
        </div>
        <div class="grid gap-1">
            <Label for="filter-to" class="text-xs text-muted-foreground"
                >Hasta</Label
            >
            <Input
                id="filter-to"
                v-model="to"
                type="date"
                class="w-40"
                :min="from || undefined"
            />
        </div>
    </div>
</template>
