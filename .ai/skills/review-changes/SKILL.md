---
name: review-changes
description: "Reviews a finished change against StudyHub's Laravel and Vue risks: missing authorization, data leaked through Inertia props, N+1 queries, unsafe rendering, missing tests. Use before declaring work complete, before committing, or when the user asks to review, check, or audit a change."
---

# Review Changes

Review the changed files, then fix what you find. Read each file; do not rely on memory of writing it.

## Find the changes

Use `git status` and `git diff`. If this is not a git repository, list the files you touched.

## Check each area

**Authorization**
- Does every new route that touches user-owned data check a policy?
- Can a user reach another user's record by changing an ID in the URL?
- Does a Form Request's `authorize()` return a bare `true` with no check elsewhere?

**Data exposure**
- Is an Eloquent model passed straight to `Inertia::render()` or `share()`?
- Do the props contain fields the page never uses? Anything in props is visible in the page source.

**Input**
- Is every written field validated in a Form Request?
- Is `$request->all()` passed to `create()` or `update()`? Use `$request->validated()`.
- Does raw SQL interpolate user input?

**Queries**
- Does a Resource or loop touch an unloaded relation?
- Is every list paginated? Do filtered and joined columns have indexes?

**Frontend**
- Is `v-html` used on user content? Do props get mutated?
- Are loading, empty, and validation error states handled?
- Does the Vue code call `fetch` or use a hardcoded URL that no longer matches a route?

**Tests**
- Do tests cover validation failure, guest access, and wrong-user access?
- Would the test fail if the feature broke?

## Report

List what you fixed and anything left open, with `file:line`. Then run the Definition of Done. Do not report a check as passed unless you ran it.
