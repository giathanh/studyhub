---
paths:
  - app/**
  - routes/**
  - database/**
  - config/**
  - bootstrap/**
---

# Backend

## Controllers stay thin
Controllers receive a Form Request, call the model or an action, and return an Inertia response or redirect. Prefer resourceful controllers. Move logic that spans several models or has side effects into a single-purpose class in `app/Actions`.

## Authorization goes through Policies
The base `App\Http\Controllers\Controller` is empty, so `$this->authorize()` does not exist. Use `Gate::authorize('update', $course)`, the Form Request `authorize()` method, or `can:` route middleware. Every route that reads or changes user-owned data needs one of these.

## Never pass raw models to Inertia
Inertia props are readable in the page source. Pass an API Resource or an explicit array containing only the fields the page uses. Resources arrive in Vue wrapped in `data` (`props.course.data.title`), and collections likewise.

## Keep shared props small
`HandleInertiaRequests::share()` runs on every request. Share only what every page needs, select explicit fields (`$request->user()?->only('id', 'name')`), and wrap anything costly in a closure.

## Writes redirect
After POST, PUT, PATCH, or DELETE, return a redirect such as `to_route('courses.index')`, not an Inertia render. Use `Inertia::flash()` for one-time messages.

## Models use attributes
Follow `App\Models\User`: declare `#[Fillable([...])]` and `#[Hidden([...])]` attributes, and casts in a `casts()` method. Never use `$guarded = []`.

## Queries
Eager load relations that a Resource touches and paginate every list. Do not call `env()` outside `config/`.

## Migrations
Do not edit a migration that has already run; add a new one. Avoid MySQL-only SQL, because tests run on SQLite.
