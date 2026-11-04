---
name: fullstack-feature
description: "Builds a feature end to end across Laravel and Inertia Vue, in order: migration, model, policy, form request, resource, controller, route, page, tests. Use when adding a new resource, CRUD screen, or any user-facing feature that touches both backend and frontend, even if the user only describes the outcome, such as 'add courses' or 'let students bookmark lessons'."
---

# Full-Stack Feature

Build from the data outward so each layer depends only on finished work. Read `.ai/rules/backend.md`, `.ai/rules/frontend.md`, and `.ai/rules/testing.md` first.

## 1. Pin down the scope

State which roles can do what, and which fields exist. If ownership or authorization is unclear, ask: it changes the schema and the policy.

## 2. Look for an existing pattern

Open the closest sibling feature and copy its structure. Search before creating a component, action, or resource.

## 3. Build in this order

Use `php artisan make:*` with `--no-interaction` for each file.

| Step | Output | Check |
|---|---|---|
| Data | Migration, model, factory | Foreign keys and indexes; `#[Fillable]`; a factory for every model |
| Access | Policy | One method per action, covering owner and non-owner |
| Input | Form Requests | Separate store and update requests when rules differ |
| Output | API Resource | Only the fields the page displays |
| Flow | Controller, route | Thin, named, behind `auth` middleware where needed |
| UI | `resources/js/pages/<Domain>/*.vue` | Activate `vue-component` and `inertia-vue-development` |
| Proof | Feature test | Covers the cases in `.ai/rules/testing.md` |

Write the feature test straight after the route, before the page, if the behavior is clear: `assertInertia` then fails until the page exists, which confirms the render name.

## 4. Check it in the browser

Run `npm run build`, open `https://studyhub.test`, and use the feature. Check the browser logs for errors. Passing tests do not prove the Vue page renders.

## 5. Finish

Activate `review-changes`, then complete the Definition of Done.
