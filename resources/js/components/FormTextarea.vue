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
        <label :for="name" class="block text-sm font-medium text-ink">
            {{ label }}
        </label>
        <textarea
            :id="name"
            :name="name"
            :rows="rows"
            :value="value ?? ''"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error ? `${name}-error` : undefined"
            class="control mt-1.5"
            :class="{ 'font-mono': monospace }"
        />
        <p v-if="hint" class="mt-1 text-sm text-ink-faint">{{ hint }}</p>
        <p v-if="error" :id="`${name}-error`" class="mt-1 text-sm text-danger">
            {{ error }}
        </p>
    </div>
</template>
