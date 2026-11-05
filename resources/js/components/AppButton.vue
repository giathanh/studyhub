<script setup lang="ts">
import type { Component } from 'vue';

type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'ghost' | 'highlight';
type ButtonSize = 'sm' | 'md';

withDefaults(
    defineProps<{
        as?: string | Component;
        variant?: ButtonVariant;
        size?: ButtonSize;
    }>(),
    {
        as: 'button',
        variant: 'primary',
        size: 'md',
    },
);

const variantClasses: Record<ButtonVariant, string> = {
    primary: 'border-brand bg-brand text-white hover:border-brand-strong hover:bg-brand-strong',
    secondary: 'border-line-strong/50 bg-surface text-ink hover:border-line-strong hover:bg-sunken',
    danger: 'border-danger/40 bg-surface text-danger hover:border-danger hover:bg-danger-soft',
    ghost: 'border-transparent text-ink-soft hover:bg-sunken hover:text-ink',
    highlight: 'border-highlight bg-highlight text-ink hover:border-highlight-soft hover:bg-highlight-soft',
};

const sizeClasses: Record<ButtonSize, string> = {
    sm: 'px-3 py-1.5',
    md: 'px-4 py-2.5',
};
</script>

<template>
    <component
        :is="as"
        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg border text-sm font-medium whitespace-nowrap transition-colors active:translate-y-px disabled:cursor-not-allowed disabled:opacity-50 motion-reduce:active:translate-y-0"
        :class="[variantClasses[variant], sizeClasses[size]]"
    >
        <slot />
    </component>
</template>
