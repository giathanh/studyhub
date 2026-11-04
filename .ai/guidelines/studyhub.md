# StudyHub

Project decisions. Where these conflict with generic framework guidance in this file, these win.

## Architecture

- Inertia monolith: Laravel owns routing, validation, and authorization; Vue renders pages. Do not add `routes/api.php`, vue-router, Pinia, or axios unless the user asks.
- TypeScript on the frontend (`<script setup lang="ts">`, strict mode). PHPUnit, not Pest. Do not migrate away from either on your own.
- Authentication is Laravel Fortify with Inertia pages. Enabled features: registration and password update. Do not enable other Fortify features unless the user asks.
- Ask before adding any Composer or npm dependency.

## Environment (Lerd)

- The app is served at `https://studyhub.test` by Lerd. Do not run `php artisan serve`.
- `php` and `composer` are Lerd shims that run inside the project's Linux container; `node` and `npm` use the project's pinned version. If `which php` does not point at Lerd, prefix commands: `lerd php artisan migrate`, `lerd composer install`, `lerd npm run build`.
- `DB_HOST=lerd-mysql` only resolves inside the container. A MySQL client on the host cannot reach it, so use Artisan through the shim or the Boost `database-query` tool.
- Development runs on MySQL; tests run on in-memory SQLite (`phpunit.xml`). Code and migrations must work on both.
- `.npmrc` sets `ignore-scripts=true`. Do not override it.

## Definition of Done

Boost does not enforce tests on a project this small, so this section does. Before reporting work as finished:

1. Every behavior change has a test, and the affected tests pass (`php artisan test --compact`).
2. PHP is formatted: `vendor/bin/pint --dirty --format agent`. Outside a git repository `--dirty` errors, so drop that flag.
3. If `resources/` changed, `npm run types:check` and `npm run build` succeed.

Report any step that failed or was skipped, with its output.

## Project Rules and Skills

- Area rules live in `.ai/rules` and project skills in `.ai/skills`. Edit those sources, never the generated copies in `.claude/skills` or `.agents/skills`, and never the Boost block in `AGENTS.md` or `CLAUDE.md`.
- After changing anything in `.ai/`, run `php artisan boost:update` and `php artisan boost:index-rules`.
- Activate `fullstack-feature` for any feature that spans backend and frontend, `vue-component` for Vue work, and `review-changes` before declaring a change complete.
