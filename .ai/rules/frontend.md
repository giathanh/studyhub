---
paths:
  - resources/js/**
  - resources/views/**
  - resources/css/**
  - vite.config.js
---

# Frontend

## Directory layout
- `resources/js/pages`: Inertia pages. PascalCase, grouped by domain, and the path is the render name: `pages/Courses/Index.vue` is `Inertia::render('Courses/Index')`.
- `resources/js/components`: reusable components, PascalCase.
- `resources/js/layouts`: page layouts.
- `resources/js/composables`: `useThing.ts` functions.
- `resources/js/types`: shared types and declaration merging, such as `InertiaConfig` in `inertia.d.ts`.

## Vue style
Use `<script setup lang="ts">` with the Composition API. Do not use the Options API. Declare props with `defineProps<{...}>()`, and `withDefaults` for defaults. Avoid `any`.

## The server owns state
Do not copy server data into a client store. Read page data from props and shared data from `usePage()`. When a shared prop changes in `HandleInertiaRequests`, update `resources/js/types/inertia.d.ts`. Use `ref` only for UI state such as open/closed.

## Forms and requests
Use Inertia `<Form>` or `useForm`, and render validation errors next to each field. Use `useHttp` for requests that should not navigate. Never use `fetch` or axios. Client-side checks never replace server validation.

## Navigation and URLs
Use `<Link>`, not `<a>`, for internal pages. No client-side route helper is installed, so write literal paths that match `routes/web.php`, or pass URLs from the server as props. Do not add Ziggy or Wayfinder without asking.

## Blade
`resources/views/app.blade.php` is the only page shell. Do not build new pages in Blade; mail templates are the exception.

## Security
Never use `v-html` on user content. Only `VITE_*` variables reach the browser, so never put secrets in them.

## Styling
Use Tailwind utilities in templates. Add `<style>` blocks only for what utilities cannot express, and put design tokens in `@theme` in `resources/css/app.css`.
