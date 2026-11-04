<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuroraText from '@/components/AuroraText.vue';
import BlurFade from '@/components/BlurFade.vue';
import BorderBeam from '@/components/BorderBeam.vue';
import MagicCard from '@/components/MagicCard.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();
const userName = computed(() => page.props.auth.user?.name ?? '');
</script>

<template>
    <AppLayout title="Dashboard">
        <template #heading>
            <section
                class="relative mb-4 rounded-2xl border border-gray-200 bg-white/80 p-8 shadow-sm backdrop-blur-sm sm:p-10"
            >
                <BorderBeam :size="160" :duration="10" />

                <p class="text-sm font-medium text-brand">Dashboard</p>
                <h1
                    class="mt-2 text-3xl font-semibold tracking-tight wrap-anywhere text-gray-900 sm:text-4xl"
                >
                    Welcome, <AuroraText>{{ userName }}</AuroraText>
                </h1>
                <p class="mt-3 max-w-xl text-gray-600">
                    This is your home on StudyHub. Your account is ready to go.
                </p>
            </section>
        </template>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <BlurFade :delay="0.1" class="sm:col-span-2 lg:row-span-2">
                <MagicCard class="grid h-full min-h-64 p-6">
                    <div class="flex h-full flex-col items-center justify-center text-center">
                        <span
                            class="flex size-14 items-center justify-center rounded-2xl bg-brand/10 text-brand"
                        >
                            <svg
                                aria-hidden="true"
                                class="size-7"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="M12 6.04A8.97 8.97 0 0 0 6 3.75c-1.05 0-2.06.18-3 .51v14.25A9 9 0 0 1 6 18c2.3 0 4.41.87 6 2.29m0-14.25a8.97 8.97 0 0 1 6-2.29c1.05 0 2.06.18 3 .51v14.25A9 9 0 0 0 18 18a8.97 8.97 0 0 0-6 2.29m0-14.25v14.25"
                                />
                            </svg>
                        </span>
                        <h2 class="mt-4 text-lg font-semibold tracking-tight text-gray-900">
                            Your study space is empty
                        </h2>
                        <p class="mt-1 max-w-sm text-sm text-gray-600">
                            New learning tools will show up here as they arrive on StudyHub.
                        </p>
                    </div>
                </MagicCard>
            </BlurFade>

            <BlurFade :delay="0.18">
                <MagicCard class="grid h-full p-6">
                    <div class="flex min-w-0 items-center gap-4">
                        <UserAvatar :name="userName" size="md" />
                        <div class="min-w-0">
                            <h2 class="text-sm font-medium text-gray-600">Account</h2>
                            <p class="truncate font-semibold text-gray-900">{{ userName }}</p>
                            <p class="mt-0.5 flex items-center gap-1.5 text-sm text-green-700">
                                <span aria-hidden="true" class="size-1.5 rounded-full bg-green-600" />
                                Signed in
                            </p>
                        </div>
                    </div>
                </MagicCard>
            </BlurFade>

            <BlurFade :delay="0.26">
                <Link
                    href="/settings/password"
                    class="group block h-full rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                >
                    <MagicCard class="grid h-full p-6">
                        <div>
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-medium text-gray-600">Security</h2>
                                <svg
                                    aria-hidden="true"
                                    class="size-4 text-gray-400 transition-all group-hover:translate-x-0.5 group-hover:text-brand motion-reduce:transition-none"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M5 12h14m-6-6 6 6-6 6" />
                                </svg>
                            </div>
                            <p class="mt-1 font-semibold text-gray-900">Change password</p>
                            <p class="mt-0.5 text-sm text-gray-600">
                                Keep your account safe with a strong password.
                            </p>
                        </div>
                    </MagicCard>
                </Link>
            </BlurFade>
        </div>
    </AppLayout>
</template>
