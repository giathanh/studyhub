<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppButton from '@/components/AppButton.vue';
import BrandMark from '@/components/BrandMark.vue';
import ProgressBar from '@/components/ProgressBar.vue';

const page = usePage();

const features = [
    {
        title: 'Courses you own',
        description: 'Write lessons in Markdown, or import a folder of files or a public GitHub repository.',
    },
    {
        title: 'Notes in context',
        description: 'Link commands and takeaways to a course or lesson, then search them when you need them.',
    },
    {
        title: 'Time you can see',
        description: 'Log each study session and watch your week add up on the dashboard.',
    },
];

const sampleLessons = [
    { title: 'Reading the problem carefully', isDone: true },
    { title: 'Writing it in your own words', isDone: true },
    { title: 'Testing what you remember', isDone: false },
];
</script>

<template>
    <div class="min-h-screen bg-paper">
        <Head title="Home" />

        <header class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-10">
            <Link href="/" class="rounded text-2xl">
                <BrandMark />
            </Link>
            <nav aria-label="Main" class="flex items-center gap-2">
                <AppButton v-if="page.props.auth.user" :as="Link" href="/dashboard">Dashboard</AppButton>
                <template v-else>
                    <AppButton :as="Link" href="/login" variant="ghost">Log in</AppButton>
                    <AppButton :as="Link" href="/register">Register</AppButton>
                </template>
            </nav>
        </header>

        <main class="mx-auto max-w-6xl animate-page-in px-4 motion-reduce:animate-none sm:px-6 lg:px-10">
            <section class="grid items-center gap-12 py-12 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:py-20">
                <div>
                    <h1 class="font-serif text-5xl leading-[1.05] font-medium tracking-tight text-ink sm:text-6xl lg:text-7xl">
                        Everything you’re learning, <span class="marker whitespace-nowrap">in one place</span>.
                    </h1>
                    <p class="mt-6 max-w-xl text-lg text-ink-soft">
                        StudyHub keeps your courses, lessons, notes, and study time together, so you always know
                        what comes next.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-2">
                        <AppButton v-if="page.props.auth.user" :as="Link" href="/dashboard">Go to dashboard</AppButton>
                        <template v-else>
                            <AppButton :as="Link" href="/register">Create your account</AppButton>
                            <AppButton :as="Link" href="/login" variant="secondary">Log in</AppButton>
                        </template>
                    </div>
                </div>

                <div aria-hidden="true" class="rounded-xl border border-line bg-surface p-6 shadow-sm">
                    <p class="text-xs font-medium text-ink-faint">Example course</p>
                    <p class="mt-1 font-serif text-2xl font-medium tracking-tight text-ink">How to study well</p>
                    <div class="mt-4 flex items-center gap-3 text-xs text-ink-soft">
                        <ProgressBar :value="67" label="Example course progress" />
                        <span class="shrink-0">2 of 3</span>
                    </div>
                    <ul class="mt-5 divide-y divide-line border-t border-line text-sm">
                        <li v-for="lesson in sampleLessons" :key="lesson.title" class="flex items-center gap-3 py-3">
                            <span
                                class="size-2.5 shrink-0 rounded-full"
                                :class="lesson.isDone ? 'bg-success' : 'border border-line-strong'"
                            />
                            <span :class="lesson.isDone ? 'text-ink-faint' : 'marker font-medium text-ink'">
                                {{ lesson.title }}
                            </span>
                        </li>
                    </ul>
                </div>
            </section>

            <section aria-label="Features" class="grid gap-8 border-t border-line py-12 md:grid-cols-3">
                <div v-for="feature in features" :key="feature.title">
                    <h2 class="font-serif text-xl font-medium text-ink">{{ feature.title }}</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">{{ feature.description }}</p>
                </div>
            </section>
        </main>
    </div>
</template>
