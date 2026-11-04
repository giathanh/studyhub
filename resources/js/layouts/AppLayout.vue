<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, useTemplateRef } from 'vue';
import AnimatedGridPattern from '@/components/AnimatedGridPattern.vue';
import AuroraText from '@/components/AuroraText.vue';
import BlurFade from '@/components/BlurFade.vue';
import UserAvatar from '@/components/UserAvatar.vue';

defineProps<{
    title: string;
}>();

const navigationLinks = [
    {
        href: '/dashboard',
        label: 'Dashboard',
        iconPath: 'M3 11.5 12 4l9 7.5M5.5 9.5V20h13V9.5M10 20v-6h4v6',
    },
    {
        href: '/courses',
        label: 'Courses',
        iconPath:
            'M12 6.5C10.5 5 8 4.5 4 4.5v14c4 0 6.5.5 8 2 1.5-1.5 4-2 8-2v-14c-4 0-6.5.5-8 2Zm0 0v14',
    },
    {
        href: '/settings/password',
        label: 'Change password',
        iconPath:
            'M7 11V8a5 5 0 0 1 10 0v3M6 11h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1Z',
    },
];

const page = usePage();
const userName = computed(() => page.props.auth.user?.name ?? '');
const currentPath = computed(() => page.url.split('?')[0]);
const successMessage = computed(() => page.flash.success);
const sidebarHiddenStorageKey = 'studyhub.sidebar-hidden';
const isSidebarOpen = ref(false);
const isSidebarHidden = ref(localStorage.getItem(sidebarHiddenStorageKey) === 'true');
const showMenuButton = useTemplateRef<HTMLButtonElement>('showMenuButton');
const hideMenuButton = useTemplateRef<HTMLButtonElement>('hideMenuButton');

function isCurrentLink(href: string): boolean {
    return currentPath.value === href || currentPath.value.startsWith(`${href}/`);
}

function setSidebarHidden(isHidden: boolean): void {
    isSidebarHidden.value = isHidden;
    localStorage.setItem(sidebarHiddenStorageKey, String(isHidden));
}

async function showSidebar(): Promise<void> {
    isSidebarOpen.value = true;
    setSidebarHidden(false);
    await nextTick();
    hideMenuButton.value?.focus();
}

async function hideSidebar(): Promise<void> {
    isSidebarOpen.value = false;
    setSidebarHidden(true);
    await nextTick();
    showMenuButton.value?.focus();
}
</script>

<template>
    <div
        class="relative isolate min-h-screen overflow-x-clip bg-gray-50 transition-[padding] duration-200 motion-reduce:transition-none"
        :class="{ 'lg:pl-64': !isSidebarHidden }"
        @keydown.esc="isSidebarOpen = false"
    >
        <Head :title="title" />

        <div class="absolute inset-x-0 top-0 -z-10 h-120 overflow-hidden">
            <AnimatedGridPattern
                class="text-brand mask-[radial-gradient(ellipse_70%_100%_at_top,white,transparent)]"
                :num-squares="30"
                :max-opacity="0.3"
                :duration="3"
            />
        </div>

        <header
            class="sticky top-0 z-10 flex items-center gap-3 border-b border-gray-200/70 bg-white/70 px-4 py-3 backdrop-blur-md"
            :class="{ 'lg:hidden': !isSidebarHidden }"
        >
            <button
                ref="showMenuButton"
                type="button"
                aria-label="Show menu"
                aria-controls="app-sidebar"
                :aria-expanded="isSidebarOpen"
                class="cursor-pointer rounded-lg p-1.5 text-gray-700 transition-colors hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                @click="showSidebar"
            >
                <svg
                    aria-hidden="true"
                    class="size-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                >
                    <path d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>
            <Link href="/dashboard" class="text-lg font-semibold tracking-tight">
                <AuroraText>StudyHub</AuroraText>
            </Link>
        </header>

        <button
            v-if="isSidebarOpen"
            type="button"
            aria-label="Close menu"
            class="fixed inset-0 z-20 cursor-default bg-gray-900/30 lg:hidden"
            @click="isSidebarOpen = false"
        />

        <aside
            id="app-sidebar"
            class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col border-r border-gray-200/70 bg-white/90 backdrop-blur-md transition-transform duration-200 motion-reduce:transition-none"
            :class="[
                isSidebarOpen ? 'translate-x-0' : 'invisible -translate-x-full',
                isSidebarHidden
                    ? 'lg:invisible lg:-translate-x-full'
                    : 'lg:visible lg:translate-x-0',
            ]"
        >
            <div class="flex items-center justify-between py-5 pr-3 pl-6">
                <Link
                    href="/dashboard"
                    class="rounded-md text-xl font-semibold tracking-tight focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                >
                    <AuroraText>StudyHub</AuroraText>
                </Link>
                <button
                    ref="hideMenuButton"
                    type="button"
                    aria-label="Hide menu"
                    aria-controls="app-sidebar"
                    class="cursor-pointer rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                    @click="hideSidebar"
                >
                    <svg
                        aria-hidden="true"
                        class="size-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.75"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M9 4v16M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Zm10 5-3 3 3 3" />
                    </svg>
                </button>
            </div>

            <nav aria-label="Main" class="flex flex-1 flex-col gap-1 overflow-y-auto px-3 text-sm">
                <Link
                    v-for="link in navigationLinks"
                    :key="link.href"
                    :href="link.href"
                    :aria-current="isCurrentLink(link.href) ? 'page' : undefined"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand aria-[current=page]:bg-brand/10 aria-[current=page]:text-brand"
                >
                    <svg
                        aria-hidden="true"
                        class="size-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.75"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path :d="link.iconPath" />
                    </svg>
                    {{ link.label }}
                </Link>
            </nav>

            <div class="flex flex-col gap-3 border-t border-gray-200/70 p-4 text-sm">
                <div class="flex min-w-0 items-center gap-3">
                    <UserAvatar :name="userName" />
                    <span class="truncate font-medium text-gray-900">{{ userName }}</span>
                </div>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="w-full cursor-pointer rounded-lg border border-gray-200 bg-white px-3 py-1.5 font-medium text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                >
                    Log out
                </Link>
            </div>
        </aside>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
            <BlurFade>
                <p
                    v-if="successMessage"
                    role="status"
                    class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800"
                >
                    {{ successMessage }}
                </p>

                <slot name="heading">
                    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-gray-900">
                        {{ title }}
                    </h1>
                </slot>

                <slot />
            </BlurFade>
        </main>
    </div>
</template>
