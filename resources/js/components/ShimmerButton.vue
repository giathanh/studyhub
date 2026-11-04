<script setup lang="ts">
/** Vue port of Magic UI's Shimmer Button (MIT): https://magicui.design/docs/components/shimmer-button */
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        as?: string | Component;
        shimmerColor?: string;
        shimmerSize?: string;
        shimmerDuration?: string;
        borderRadius?: string;
        background?: string;
    }>(),
    {
        as: 'button',
        shimmerColor: '#ffffff',
        shimmerSize: '0.05em',
        shimmerDuration: '3s',
        borderRadius: '0.5rem',
        background: 'var(--color-gray-900)',
    },
);
</script>

<template>
    <component
        :is="as"
        class="group relative z-0 flex transform-gpu cursor-pointer items-center justify-center overflow-hidden [border-radius:var(--radius)] border border-white/10 [background:var(--bg)] px-6 py-3 text-sm font-medium whitespace-nowrap text-white transition-transform duration-300 ease-in-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand active:translate-y-px disabled:cursor-not-allowed disabled:opacity-50"
        :style="{
            '--spread': '90deg',
            '--shimmer-color': shimmerColor,
            '--radius': borderRadius,
            '--speed': shimmerDuration,
            '--cut': shimmerSize,
            '--bg': background,
        }"
    >
        <div class="absolute inset-0 -z-30 @container-[size] overflow-visible blur-[2px]">
            <div
                class="absolute inset-0 aspect-[1] h-[100cqh] animate-shimmer-slide rounded-none [mask:none] motion-reduce:animate-none"
            >
                <div
                    class="absolute -inset-full w-auto rotate-0 animate-spin-around [translate:0_0] [background:conic-gradient(from_calc(270deg-(var(--spread)*0.5)),transparent_0,var(--shimmer-color)_var(--spread),transparent_var(--spread))] motion-reduce:animate-none"
                />
            </div>
        </div>

        <slot />

        <div
            class="absolute inset-0 size-full transform-gpu rounded-2xl shadow-[inset_0_-8px_10px_#ffffff1f] transition-all duration-300 ease-in-out group-hover:shadow-[inset_0_-6px_10px_#ffffff3f] group-active:shadow-[inset_0_-10px_10px_#ffffff3f]"
        />
        <div
            class="absolute inset-(--cut) -z-20 [border-radius:var(--radius)] [background:var(--bg)]"
        />
    </component>
</template>
