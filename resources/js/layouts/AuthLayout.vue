<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BrandMark from '@/components/BrandMark.vue';

defineProps<{
    title: string;
}>();

const highlights = [
    'Courses and lessons written in Markdown',
    'Notes linked to what you are studying',
    'A weekly count of the time you put in',
];
</script>

<template>
    <div class="grid min-h-screen bg-paper lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
        <Head :title="title" />

        <aside class="hidden flex-col justify-between bg-ink p-12 text-paper lg:flex">
            <p class="font-serif text-4xl leading-tight font-medium tracking-tight xl:text-5xl">
                Keep what you learn where you can find it.
            </p>
            <ul class="flex flex-col gap-3 border-t border-paper/20 pt-6 text-sm text-paper/80">
                <li v-for="highlight in highlights" :key="highlight">{{ highlight }}</li>
            </ul>
        </aside>

        <main class="flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-sm animate-page-in motion-reduce:animate-none">
                <Link href="/" class="inline-block rounded text-2xl">
                    <BrandMark />
                </Link>

                <h1 class="mt-8 mb-6 font-serif text-3xl font-medium tracking-tight text-ink">
                    {{ title }}
                </h1>

                <slot />
            </div>
        </main>
    </div>
</template>
