---
paths:
  - tests/**
---

# Testing

## Cover each route's failure paths
For each new route, test the success path, validation failure, guest access, and access by the wrong user. Authorization tests are not optional.

## Assert Inertia responses
Use `assertInertia` with `Inertia\Testing\AssertableInertia`. It also fails when the page file is missing, because `inertia.testing.ensure_pages_exist` is on, so these tests catch misnamed components.

```php
$this->actingAs($user)
    ->get(route('courses.index'))
    ->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Courses/Index')
        ->has('courses.data', 3));
```

## Database and side effects
Use `RefreshDatabase` and factories. Tests run on in-memory SQLite. Fake external effects with `Http::fake()`, `Mail::fake()`, `Queue::fake()`, and `Storage::fake()`. Tests never touch the network.

## Frontend tests
No JavaScript test runner is installed. Cover page behavior through feature tests and do not add Vitest or Playwright without asking.
