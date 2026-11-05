<script setup lang="ts">
import type { StatusOption } from '@/types/course';

withDefaults(
    defineProps<{
        label: string;
        name: string;
        options: StatusOption[];
        value?: string;
        error?: string;
    }>(),
    {
        value: undefined,
        error: undefined,
    },
);
</script>

<template>
    <div>
        <label :for="name" class="block text-sm font-medium text-ink">
            {{ label }}
        </label>
        <select
            :id="name"
            :name="name"
            :value="value"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error ? `${name}-error` : undefined"
            class="control mt-1.5"
        >
            <option v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
        <p v-if="error" :id="`${name}-error`" class="mt-1 text-sm text-danger">
            {{ error }}
        </p>
    </div>
</template>
