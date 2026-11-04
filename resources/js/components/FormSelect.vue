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
        <label :for="name" class="block text-sm font-medium text-gray-900">
            {{ label }}
        </label>
        <select
            :id="name"
            :name="name"
            :value="value"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error ? `${name}-error` : undefined"
            class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs transition-shadow focus:border-brand focus:ring-3 focus:ring-brand/20 focus:outline-none aria-invalid:border-red-500"
        >
            <option v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
        <p v-if="error" :id="`${name}-error`" class="mt-1 text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>
