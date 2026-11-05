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
        <label :for="name" class="block text-sm font-medium text-ink">
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
            class="control mt-1.5"
        />
        <p v-if="error" :id="`${name}-error`" class="mt-1 text-sm text-danger">
            {{ error }}
        </p>
    </div>
</template>
