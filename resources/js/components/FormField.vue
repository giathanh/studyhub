<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        name: string;
        type?: string;
        autocomplete?: string;
        autofocus?: boolean;
        error?: string;
        required?: boolean;
        value?: string | null;
    }>(),
    {
        type: 'text',
        autocomplete: undefined,
        autofocus: false,
        error: undefined,
        required: true,
        value: undefined,
    },
);
</script>

<template>
    <div>
        <label :for="name" class="block text-sm font-medium text-gray-900">
            {{ label }}
        </label>
        <input
            :id="name"
            :name="name"
            :type="type"
            :autocomplete="autocomplete"
            :autofocus="autofocus"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error ? `${name}-error` : undefined"
            :required="required"
            :value="value"
            class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs transition-shadow focus:border-brand focus:ring-3 focus:ring-brand/20 focus:outline-none aria-invalid:border-red-500"
        />
        <p v-if="error" :id="`${name}-error`" class="mt-1 text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>
