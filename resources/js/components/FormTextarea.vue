<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        name: string;
        rows?: number;
        value?: string | null;
        hint?: string;
        error?: string;
        monospace?: boolean;
    }>(),
    {
        rows: 4,
        value: undefined,
        hint: undefined,
        error: undefined,
        monospace: false,
    },
);
</script>

<template>
    <div>
        <label :for="name" class="block text-sm font-medium text-gray-900">
            {{ label }}
        </label>
        <textarea
            :id="name"
            :name="name"
            :rows="rows"
            :value="value ?? ''"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error ? `${name}-error` : undefined"
            class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs transition-shadow focus:border-brand focus:ring-3 focus:ring-brand/20 focus:outline-none aria-invalid:border-red-500"
            :class="{ 'font-mono': monospace }"
        />
        <p v-if="hint" class="mt-1 text-sm text-gray-500">{{ hint }}</p>
        <p v-if="error" :id="`${name}-error`" class="mt-1 text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>
