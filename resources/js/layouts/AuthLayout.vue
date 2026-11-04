<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AnimatedGridPattern from '@/components/AnimatedGridPattern.vue';
import AuroraText from '@/components/AuroraText.vue';
import BlurFade from '@/components/BlurFade.vue';
import BorderBeam from '@/components/BorderBeam.vue';

defineProps<{
    title: string;
}>();
</script>

<template>
    <main
        class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gray-50 px-4 py-10"
    >
        <Head :title="title" />

        <AnimatedGridPattern
            class="text-brand mask-[radial-gradient(600px_circle_at_center,white,transparent)]"
            :num-squares="30"
            :max-opacity="0.3"
            :duration="3"
        />

        <BlurFade class="relative w-full max-w-sm">
            <Link href="/" class="block text-center text-2xl font-semibold tracking-tight">
                <AuroraText>StudyHub</AuroraText>
            </Link>

            <section
                class="relative mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <BorderBeam :size="120" :duration="8" />

                <h1 class="text-xl font-semibold tracking-tight text-gray-900">
                    {{ title }}
                </h1>

                <div class="mt-6">
                    <slot />
                </div>
            </section>
        </BlurFade>
    </main>
</template>
