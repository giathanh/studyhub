<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, useTemplateRef } from 'vue';
import BrandMark from '@/components/BrandMark.vue';
import PageHeader from '@/components/PageHeader.vue';
import ScrollToTopButton from '@/components/ScrollToTopButton.vue';
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
        href: '/notes',
        label: 'Notes',
        iconPath:
            'M7 4h7l5 5v11a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Zm7 0v5h5M9 13h6M9 17h4',
    },
    {
        href: '/study-sessions',
        label: 'Study sessions',
        iconPath: 'M12 8v4l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0',
    },
];

const passwordLinkHref = '/settings/password';

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
        class="min-h-screen overflow-x-clip bg-paper transition-[padding] duration-200 motion-reduce:transition-none"
        :class="{ 'lg:pl-60': !isSidebarHidden }"
        @keydown.esc="isSidebarOpen = false"
    >
        <Head :title="title" />

        <header
            class="sticky top-0 z-10 flex items-center gap-3 border-b border-line bg-paper px-4 py-3"
            :class="{ 'lg:hidden': !isSidebarHidden }"
        >
            <button
                ref="showMenuButton"
                type="button"
                aria-label="Show menu"
                aria-controls="app-sidebar"
                :aria-expanded="isSidebarOpen"
                class="cursor-pointer rounded-lg p-1.5 text-ink-soft transition-colors hover:bg-sunken hover:text-ink"
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
            <Link href="/dashboard" class="rounded text-xl">
                <BrandMark />
            </Link>
        </header>

        <button
            v-if="isSidebarOpen"
            type="button"
            aria-label="Close menu"
            class="fixed inset-0 z-20 cursor-default bg-ink/30 lg:hidden"
            @click="isSidebarOpen = false"
        />

        <aside
            id="app-sidebar"
            class="fixed inset-y-0 left-0 z-30 flex w-60 flex-col border-r border-line bg-surface transition-transform duration-200 motion-reduce:transition-none"
            :class="[
                isSidebarOpen ? 'translate-x-0' : 'invisible -translate-x-full',
                isSidebarHidden
                    ? 'lg:invisible lg:-translate-x-full'
                    : 'lg:visible lg:translate-x-0',
            ]"
        >
            <div class="flex items-center justify-between py-5 pr-3 pl-5">
                <Link href="/dashboard" class="rounded text-2xl">
                    <BrandMark />
                </Link>
                <button
                    ref="hideMenuButton"
                    type="button"
                    aria-label="Hide menu"
                    aria-controls="app-sidebar"
                    class="cursor-pointer rounded-lg p-1.5 text-ink-faint transition-colors hover:bg-sunken hover:text-ink"
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

            <nav aria-label="Main" class="flex flex-1 flex-col gap-0.5 overflow-y-auto px-3 py-1 text-sm">
                <Link
                    v-for="link in navigationLinks"
                    :key="link.href"
                    :href="link.href"
                    :aria-current="isCurrentLink(link.href) ? 'page' : undefined"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-ink-soft transition-colors hover:bg-sunken hover:text-ink aria-[current=page]:bg-highlight aria-[current=page]:text-ink"
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

            <div class="flex flex-col gap-3 border-t border-line p-4 text-sm">
                <div class="flex min-w-0 items-center gap-3">
                    <UserAvatar :name="userName" />
                    <span class="truncate font-medium text-ink">{{ userName }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <Link
                        :href="passwordLinkHref"
                        :aria-current="isCurrentLink(passwordLinkHref) ? 'page' : undefined"
                        class="rounded text-ink-soft underline-offset-4 transition-colors hover:text-ink hover:underline aria-[current=page]:font-medium aria-[current=page]:text-ink"
                    >
                        Change password
                    </Link>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="-mr-2 cursor-pointer rounded-lg px-2 py-1 font-medium text-ink-soft transition-colors hover:bg-sunken hover:text-ink"
                    >
                        Log out
                    </Link>
                </div>
            </div>
        </aside>

        <main class="mx-auto max-w-6xl animate-page-in px-4 py-8 motion-reduce:animate-none sm:px-6 sm:py-10 lg:px-10">
            <p
                v-if="successMessage"
                role="status"
                class="mb-6 rounded-lg bg-success-soft px-4 py-2.5 text-sm font-medium text-success"
            >
                {{ successMessage }}
            </p>

            <slot name="heading">
                <PageHeader :title="title" />
            </slot>

            <slot />
        </main>

        <ScrollToTopButton />
    </div>
</template>
