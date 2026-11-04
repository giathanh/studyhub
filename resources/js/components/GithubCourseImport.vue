<script setup lang="ts">
import { useForm, useHttp } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import MagicCard from '@/components/MagicCard.vue';
import ShimmerButton from '@/components/ShimmerButton.vue';
import { useUnsavedChanges } from '@/composables/useUnsavedChanges';

type Preview = { token: string; title: string; files: { path: string; size: number }[] };
const lookup = useHttp<{ repository_url: string }, Preview>({ repository_url: '' });
const preview = ref<Preview | null>(null);
const networkError = ref('');
const loadedRepository = ref('');
const form = useForm({ token: '', title: '', paths: [] as string[] });
const groups = computed(() => {
    const folders = new Map<string, Preview['files']>();
    for (const file of preview.value?.files ?? []) {
        const name = file.path.includes('/') ? file.path.split('/')[0] : 'Top-level files';
        folders.set(name, [...(folders.get(name) ?? []), file]);
    }
    return [...folders].map(([name, files]) => ({ name, files }));
});
const selectedBytes = computed(() => (preview.value?.files ?? [])
    .filter((file) => form.paths.includes(file.path)).reduce((sum, file) => sum + file.size, 0));
const errors = computed(() => Object.values(form.errors).filter(Boolean));
useUnsavedChanges(() => preview.value !== null && !form.processing && !form.wasSuccessful);

async function loadRepository(): Promise<void> {
    preview.value = null;
    networkError.value = '';
    form.reset();
    form.clearErrors();
    try {
        const result = await lookup.post('/courses/import/github/preview');
        preview.value = result;
        loadedRepository.value = lookup.repository_url;
        form.token = result.token;
        form.title = result.title;
        form.paths = result.files.map((file) => file.path);
    } catch {
        if (!lookup.hasErrors) networkError.value = 'Could not load the repository. Check your connection and try again shortly.';
    }
}

function toggleGroup(paths: string[]): void {
    form.paths = paths.every((path) => form.paths.includes(path))
        ? form.paths.filter((path) => !paths.includes(path))
        : [...new Set([...form.paths, ...paths])];
}
</script>

<template>
    <MagicCard class="mb-6 max-w-2xl p-6">
        <h2 class="text-lg font-semibold text-gray-900">Import from GitHub</h2>
        <p class="mt-1 text-sm text-gray-600">Paste a public repository link, then choose the Markdown files to import. Each import creates a new course.</p>
        <form class="mt-4 space-y-3" @submit.prevent="loadRepository">
            <label for="repository-url" class="block text-sm font-medium text-gray-900">Repository URL</label>
            <input id="repository-url" v-model="lookup.repository_url" type="url" required
                placeholder="https://github.com/owner/repository" :disabled="lookup.processing || form.processing"
                :aria-invalid="lookup.errors.repository_url ? 'true' : undefined" aria-describedby="repository-error"
                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:outline-none" />
            <p v-if="lookup.errors.repository_url || networkError" id="repository-error" role="alert" class="text-sm text-red-600">{{ lookup.errors.repository_url || networkError }}</p>
            <ShimmerButton type="submit" :disabled="lookup.processing || form.processing">
                {{ lookup.processing ? 'Loading repository…' : 'Load repository' }}
            </ShimmerButton>
        </form>
        <p v-if="preview" class="mt-4 break-all text-sm text-gray-500">Loaded: {{ loadedRepository }}</p>
        <form v-if="preview" class="mt-5 space-y-4" @submit.prevent="form.post('/courses/import/github')">
            <div>
                <label for="github-course-title" class="block text-sm font-medium text-gray-900">Course title</label>
                <input id="github-course-title" v-model="form.title" maxlength="255" :disabled="form.processing"
                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:outline-none" />
            </div>
            <p aria-live="polite" class="text-sm text-gray-600">{{ form.paths.length }} of {{ preview.files.length }} files selected · {{ (selectedBytes / 1024 / 1024).toFixed(2) }} / 7 MB</p>
            <p class="text-sm text-gray-500">Folders become sections. Each file becomes a lesson; a single file splits at H2 headings. Preview expires after 30 minutes.</p>
            <fieldset :disabled="form.processing" class="max-h-80 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-3">
                <legend class="sr-only">Choose Markdown files</legend>
                <details v-for="group in groups" :key="group.name" class="text-sm">
                    <summary class="cursor-pointer break-all py-1 font-medium text-gray-900">{{ group.name }} ({{ group.files.length }})</summary>
                    <button type="button" class="my-2 rounded text-brand underline focus-visible:outline-2" @click="toggleGroup(group.files.map((file) => file.path))">
                        {{ group.files.every((file) => form.paths.includes(file.path)) ? 'Deselect folder' : 'Select folder' }}
                    </button>
                    <label v-for="file in group.files" :key="file.path" class="flex items-start gap-2 py-1.5 text-gray-700">
                        <input v-model="form.paths" type="checkbox" :value="file.path" class="mt-0.5 shrink-0 accent-brand" />
                        <span class="min-w-0 break-all">{{ file.path }}</span>
                    </label>
                </details>
            </fieldset>
            <ul v-if="errors.length" role="alert" class="space-y-1 text-sm text-red-600">
                <li v-for="error in errors" :key="error">{{ error }}</li>
            </ul>
            <p v-if="selectedBytes > 7 * 1024 * 1024" role="alert" class="text-sm text-red-600">Choose fewer files to stay within 7 MB.</p>
            <ShimmerButton type="submit" :disabled="form.processing || form.paths.length === 0 || selectedBytes > 7 * 1024 * 1024">
                {{ form.processing ? 'Importing from GitHub…' : 'Import selected files' }}
            </ShimmerButton>
        </form>
    </MagicCard>
</template>
