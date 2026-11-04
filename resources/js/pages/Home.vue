<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AnimatedGridPattern from '@/components/AnimatedGridPattern.vue';
import AuroraText from '@/components/AuroraText.vue';
import BlurFade from '@/components/BlurFade.vue';
import ShimmerButton from '@/components/ShimmerButton.vue';

const page = usePage();
</script>

<template>
    <main
        class="relative flex min-h-screen flex-col items-center justify-center gap-8 overflow-hidden bg-gray-50 px-4"
    >
        <Head title="Home" />

        <AnimatedGridPattern
            class="text-brand mask-[radial-gradient(600px_circle_at_center,white,transparent)]"
            :num-squares="30"
            :max-opacity="0.3"
            :duration="3"
        />

        <BlurFade class="relative">
            <h1 class="text-5xl font-semibold tracking-tight sm:text-7xl">
                <AuroraText>StudyHub</AuroraText>
            </h1>
        </BlurFade>

        <BlurFade :delay="0.15" class="relative">
            <nav class="flex items-center gap-3">
                <ShimmerButton
                    v-if="page.props.auth.user"
                    :as="Link"
                    href="/dashboard"
                >
                    Dashboard
                </ShimmerButton>
                <template v-else>
                    <Link
                        href="/login"
                        class="rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-medium text-gray-900 transition-colors hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                    >
                        Log in
                    </Link>
                    <ShimmerButton :as="Link" href="/register">
                        Register
                    </ShimmerButton>
                </template>
            </nav>
        </BlurFade>
    </main>
</template>
