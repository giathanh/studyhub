<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FormField from '@/components/FormField.vue';
import MagicCard from '@/components/MagicCard.vue';
import ShimmerButton from '@/components/ShimmerButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface MarkdownFile {
    path: string;
    group: string;
    file: File;
}

const topLevelGroup = 'Top-level files';
const ignoredFolders = ['node_modules', 'vendor', 'dist', 'build'];
const maxImportBytes = 7 * 1024 * 1024;

const form = useForm<{
    title: string;
    files: { path: string; content: string }[];
}>({
    title: '',
    files: [],
});

const markdownFiles = ref<MarkdownFile[]>([]);
const excludedGroups = ref<string[]>([]);
const isReadingFiles = ref(false);

const groups = computed(() => {
    const fileCounts = new Map<string, number>();

    for (const { group } of markdownFiles.value) {
        fileCounts.set(group, (fileCounts.get(group) ?? 0) + 1);
    }

    return [...fileCounts]
        .map(([name, fileCount]) => ({ name, fileCount }))
        .sort((first, second) => first.name.localeCompare(second.name, undefined, { numeric: true }));
});

const selectedFiles = computed(() =>
    markdownFiles.value.filter(({ group }) => !excludedGroups.value.includes(group)),
);

const importHint = computed(() =>
    selectedFiles.value.length === 1
        ? 'One file: the H1 names the course and each H2 becomes a lesson.'
        : 'Each file becomes a lesson, grouped into sections by top-level folder.',
);

const errorMessages = computed(() =>
    Object.entries(form.errors as Record<string, string>)
        .filter(([field]) => field !== 'title')
        .map(([field, message]) => {
            const fileIndex = field.match(/^files\.(\d+)\./)?.[1];

            return fileIndex === undefined
                ? message
                : `${form.files[Number(fileIndex)]?.path}: ${message}`;
        }),
);

function isIgnoredPath(path: string): boolean {
    return path
        .split('/')
        .slice(0, -1)
        .some(
            (folder) =>
                ignoredFolders.includes(folder) ||
                folder.startsWith('.') ||
                folder.startsWith('_'),
        );
}

function chooseFiles(event: Event): void {
    const input = event.target as HTMLInputElement;
    const chosenFiles = Array.from(input.files ?? []);
    const rootFolder = chosenFiles[0]?.webkitRelativePath.split('/')[0] ?? '';

    markdownFiles.value = chosenFiles
        .map((file) => ({
            file,
            path: file.webkitRelativePath.split('/').slice(1).join('/') || file.name,
        }))
        .filter(({ path }) => /\.(md|markdown)$/i.test(path) && !isIgnoredPath(path))
        .map(({ file, path }) => ({
            file,
            path,
            group: path.includes('/') ? path.split('/')[0] : topLevelGroup,
        }));

    excludedGroups.value = [];
    form.title = rootFolder;
    form.clearErrors();
    input.value = '';
}

function toggleGroup(name: string): void {
    excludedGroups.value = excludedGroups.value.includes(name)
        ? excludedGroups.value.filter((group) => group !== name)
        : [...excludedGroups.value, name];
}

async function submit(): Promise<void> {
    const totalBytes = selectedFiles.value.reduce((total, { file }) => total + file.size, 0);

    if (totalBytes > maxImportBytes) {
        form.setError('files', 'These files are too large to import at once. Choose fewer folders.');

        return;
    }

    isReadingFiles.value = true;

    try {
        form.files = await Promise.all(
            selectedFiles.value.map(async ({ path, file }) => ({
                path,
                content: await file.text(),
            })),
        );
    } catch {
        form.setError('files', 'A file could not be read. Choose the files again.');

        return;
    } finally {
        isReadingFiles.value = false;
    }

    form.post('/courses/import');
}

const pickerClass =
    'cursor-pointer rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 transition-colors focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand hover:bg-gray-100';
</script>

<template>
    <AppLayout title="Import Markdown">
        <MagicCard class="max-w-2xl p-6">
            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <p class="text-sm text-gray-600">
                        Choose a folder of .md files, or one or more files. Nothing is saved until you
                        import.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <label :class="pickerClass">
                            Choose folder
                            <input
                                type="file"
                                webkitdirectory
                                class="sr-only"
                                @change="chooseFiles"
                            />
                        </label>
                        <label :class="pickerClass">
                            Choose files
                            <input
                                type="file"
                                multiple
                                accept=".md,.markdown"
                                class="sr-only"
                                @change="chooseFiles"
                            />
                        </label>
                    </div>
                </div>

                <template v-if="markdownFiles.length > 0">
                    <fieldset>
                        <legend class="text-sm font-medium text-gray-900">
                            {{ selectedFiles.length }} of {{ markdownFiles.length }} files selected
                        </legend>
                        <ul class="mt-2 max-h-72 divide-y divide-gray-200 overflow-y-auto rounded-lg border border-gray-200">
                            <li v-for="group in groups" :key="group.name">
                                <label class="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm">
                                    <input
                                        type="checkbox"
                                        class="accent-brand"
                                        :checked="!excludedGroups.includes(group.name)"
                                        @change="toggleGroup(group.name)"
                                    />
                                    <span class="min-w-0 flex-1 wrap-anywhere text-gray-900">{{ group.name }}</span>
                                    <span class="shrink-0 text-gray-500">{{ group.fileCount }}</span>
                                </label>
                            </li>
                        </ul>
                        <p class="mt-2 text-sm text-gray-500">{{ importHint }}</p>
                    </fieldset>

                    <div>
                        <FormField
                            label="Course title"
                            name="title"
                            :required="false"
                            :value="form.title"
                            :error="form.errors.title"
                            @input="form.title = ($event.target as HTMLInputElement).value"
                        />
                        <p v-if="selectedFiles.length === 1" class="mt-1 text-sm text-gray-500">
                            Leave blank to use the file's H1.
                        </p>
                    </div>
                </template>

                <p v-else class="rounded-lg bg-gray-50 px-4 py-6 text-center text-sm text-gray-600">
                    No Markdown files chosen yet.
                </p>

                <ul v-if="errorMessages.length > 0" role="alert" class="space-y-1 text-sm text-red-600">
                    <li v-for="message in errorMessages" :key="message" class="wrap-anywhere">
                        {{ message }}
                    </li>
                </ul>

                <ShimmerButton
                    type="submit"
                    :disabled="selectedFiles.length === 0 || isReadingFiles || form.processing"
                >
                    {{ isReadingFiles || form.processing ? 'Importing…' : 'Import course' }}
                </ShimmerButton>
            </form>
        </MagicCard>
    </AppLayout>
</template>
