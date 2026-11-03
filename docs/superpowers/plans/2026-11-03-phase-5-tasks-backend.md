# Phase 5 Tasks Backend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver the Laravel backend vertical slice for M5 task management: HR creates and assigns tasks, employees progress and submit them, reviewers approve or request revision, with comments and private evidence attachments.

**Architecture:** Add a new `App\Modules\Tasks` module using the repo's modular-monolith conventions. Cross-module reads go through the existing `EmployeeDirectory` contract, extended with active-employee lookup. The existing `hr.role` middleware gates HR endpoints; object-level authorization lives in a small `TaskAccess` service. State transitions are enforced in application services, not accepted from the client.

**Tech Stack:** PHP 8.4, Laravel 13, PostgreSQL 17, SQLite in-memory tests, PHPUnit feature tests, Laravel filesystem private disk.

**Spec:** `docs/architecture.md` §5.2, §5.5, §5.14, §6.4 (`tasks` through `task_comments`), §7.10; `docs/roadmap.md` §8; `docs/adr/0011-per-assignment-task-workflow.md`; `docs/adr/0014-private-attachments-and-notifications.md`.

## Global Constraints

- API prefix is `/api/v1`; every response uses `App\Shared\Http\ApiResponse`.
- Errors use the existing envelope: `success: false`, `message`, optional `errors`.
- A task may have many assignees; status/progress live on `task_assignments`, never on `tasks`.
- `tasks.status`: `draft`, `assigned`, `closed`, `cancelled`.
- `task_assignments.status`: `assigned`, `in_progress`, `submitted`, `under_review`, `revision_required`, `completed`, `cancelled`.
- `task_submissions.review_status`: `pending`, `approved`, `revision_requested`.
- Authorized reviewer: task creator or `hr`/`admin` role (MVP; no separate permission table yet).
- Evidence is required only when `tasks.requires_photo_proof = true`; an evidence row must have `kind = evidence` and a `task_submission_id`. Evidence files are uploaded as part of the submission request so the submission id exists before the attachment rows are written.
- Attachment whitelist: `jpg`, `jpeg`, `png`, `pdf`, `docx`, `xlsx`; max 10 MB; private disk only.
- A module may only import another module through `Contracts` (enforced by `tests/Architecture/ModuleBoundaryTest.php`).
- Do not commit `.env` or `..env.un~`.

## Review Focus

- An employee must never read or mutate another employee's assignment.
- A duplicate review or duplicate pending submission must not create a second decision or submission.
- A task with two assignees must close only after both assignments reach `completed` or `cancelled`.
- Evidence upload must reject a submission when `requires_photo_proof = true` and no evidence attachment exists.
- Cancelling a task must not leave an assignee able to start, progress, or submit it.

---

### Task 1: Schema, models, and HR task creation

**Files:**
- Create: `database/migrations/2026_11_03_100000_create_tasks_tables.php`
- Create: `app/Modules/Tasks/Infrastructure/Models/Task.php`
- Create: `app/Modules/Tasks/Infrastructure/Models/TaskAssignment.php`
- Create: `app/Modules/Tasks/Infrastructure/Models/TaskSubmission.php`
- Create: `app/Modules/Tasks/Infrastructure/Models/TaskAttachment.php`
- Create: `app/Modules/Tasks/Infrastructure/Models/TaskComment.php`
- Create: `app/Modules/Tasks/TasksServiceProvider.php`
- Create: `app/Modules/Tasks/Application/Support/TaskAccess.php`
- Create: `app/Modules/Tasks/Http/Requests/StoreTaskRequest.php`
- Create: `app/Modules/Tasks/Http/Controllers/HrTaskController.php`
- Create: `app/Modules/Tasks/Http/Presenters/TaskPresenter.php`
- Modify: `bootstrap/providers.php`
- Modify: `routes/api/v1/admin.php`
- Modify: `app/Modules/Employees/Contracts/EmployeeDirectory.php`
- Modify: `app/Modules/Employees/Infrastructure/Services/EloquentEmployeeDirectory.php`
- Create: `app/Modules/Identity/Contracts/UserDirectory.php`
- Create: `app/Modules/Identity/Infrastructure/Services/EloquentUserDirectory.php`
- Modify: `app/Modules/Identity/IdentityServiceProvider.php`
- Test: `tests/Feature/Api/V1/Hr/CreateTaskTest.php`

**Interfaces:**
- Consumes: `EmployeeDirectory::findByUserId(int $userId): ?EmployeeSummary`; new `EmployeeDirectory::findActiveByIds(array $ids): array` returning `list<EmployeeSummary>`; new `UserDirectory::namesByIds(array $ids): array<int, string>`.
- Produces: `TaskPresenter::summary(Task $task): array<string, mixed>`; `TaskPresenter::detail(Task $task): array<string, mixed>`; `TaskAccess::employeeIdFor(int $userId): ?int`; `TaskAccess::canView(int $userId, string $role, Task $task): bool`; `TaskAccess::canReview(int $userId, string $role, Task $task): bool`.

- [ ] **Step 1: Write the failing feature test**

Create `tests/Feature/Api/V1/Hr/CreateTaskTest.php` with tests named:
- `test_hr_creates_task_with_multiple_assignees`
- `test_task_creation_rejects_inactive_or_unknown_assignees`
- `test_employee_cannot_create_tasks`

Assert `POST /api/v1/hr/tasks` returns `201`, `data.task.status = assigned`, two `data.task.assignments`, and database rows in `tasks` and `task_assignments`.

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=CreateTaskTest`
Expected: FAIL because the route and tables do not exist.

- [ ] **Step 3: Add the migration and models**

Create the five tables exactly as specified in `docs/architecture.md` §6.4, with these implementation choices:
- `tasks.checklist_template` and `task_submissions.checklist` use `json`.
- `task_assignments.progress_percent` is `unsignedTinyInteger` default `0`; app validates `0..100`.
- `task_submissions` gets a partial unique index on `task_assignment_id` where `review_status = 'pending'` via `DB::statement`.
- `task_attachments.object_key` is unique; `size_bytes` is `unsignedBigInteger`.
- Foreign keys follow the spec; `task_assignments.employee_id` references `employees.id`.

Add Eloquent models with fillable fields, casts (`checklist_template`/`checklist` to array, timestamps to datetime), and relationships. `Task` has `assignments`, `submissions` (through assignments), `attachments`, `comments`; `TaskAssignment` has `task`, `submissions`, `employeeId` accessor only (no cross-module relation); `TaskSubmission` has `assignment`, `attachments`; `TaskComment` has `author` relation to `User` (same module? use `App\Modules\Identity\Infrastructure\Models\User` would violate the boundary — do not add the relation; expose `author_user_id` only).

- [ ] **Step 4: Extend the employee contract and register the module**

Add `findActiveByIds(array $ids): array` to `EmployeeDirectory` and implement it in `EloquentEmployeeDirectory` with `whereIn('id', $ids)->where('employment_status', '!=', 'terminated')->get()`.

Add `UserDirectory` to Identity with an Eloquent implementation that returns `[user_id => name]`, and bind it in `IdentityServiceProvider`. Register `TasksServiceProvider` in `bootstrap/providers.php`; it binds `TaskAccess` if needed.

- [ ] **Step 5: Implement HR create and list routes**

Add to `routes/api/v1/admin.php` inside the existing `hr` group:
```php
Route::get('tasks', [HrTaskController::class, 'index']);
Route::get('tasks/options', [HrTaskController::class, 'options']);
Route::post('tasks', [HrTaskController::class, 'store']);
```

`store` validates `title`, `description`, `priority` in `high,medium,low`, `division_id`, `assignee_ids` array min 1 distinct, `checklist_template` array of `{key,label}`, `requires_photo_proof` boolean, `due_at` date. Create task + assignments in one DB transaction; set task status `assigned`; return `201`.

- [ ] **Step 6: Run the test to verify it passes**

Run: `php artisan test --filter=CreateTaskTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add database/migrations app/Modules/Tasks bootstrap/providers.php routes/api/v1/admin.php app/Modules/Employees/Contracts app/Modules/Employees/Infrastructure/Services tests/Feature/Api/V1/Hr/CreateTaskTest.php
git commit -m "feat(tasks): add task schema and HR assignment"
```

---

### Task 2: Employee task list and detail

**Files:**
- Create: `app/Modules/Tasks/Http/Controllers/TaskController.php`
- Modify: `routes/api/v1/app.php`
- Test: `tests/Feature/Api/V1/App/TaskReadTest.php`

**Interfaces:**
- Consumes: `TaskAccess`, `TaskPresenter`, `EmployeeDirectory`.
- Produces: `GET /api/v1/tasks` returns `{ tasks: [...] }`; `GET /api/v1/tasks/{id}` returns task detail.

- [ ] **Step 1: Write failing tests**

Tests:
- `test_employee_sees_only_own_assignments`
- `test_task_detail_hides_another_employees_assignment`
- `test_hr_can_view_task_it_created`

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=TaskReadTest`
Expected: FAIL because routes do not exist.

- [ ] **Step 3: Implement `TaskController@index` and `show`**

Resolve the current employee via `TaskAccess::employeeIdFor`. `index` queries assignments for that employee with task relations, newest first; returns each assignment with task summary. `show` loads a task with assignments, comments, attachments; authorize via `TaskAccess::canView` (assignee, creator, or HR). Return 404 when not visible to avoid leaking existence.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=TaskReadTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Tasks routes/api/v1/app.php tests/Feature/Api/V1/App/TaskReadTest.php
git commit -m "feat(tasks): let employees read assigned tasks"
```

---

### Task 3: Start and progress transitions

**Files:**
- Create: `app/Modules/Tasks/Application/StartTask/StartTaskUseCase.php`
- Create: `app/Modules/Tasks/Application/UpdateProgress/UpdateProgressUseCase.php`
- Modify: `app/Modules/Tasks/Http/Controllers/TaskController.php`
- Modify: `routes/api/v1/app.php`
- Test: `tests/Feature/Api/V1/App/TaskProgressTest.php`

**Interfaces:**
- Produces: `StartTaskUseCase::execute(int $taskId, int $employeeId): Task`; `UpdateProgressUseCase::execute(int $taskId, int $employeeId, int $progress, array $checklist): Task`.

- [ ] **Step 1: Write failing tests**

Tests:
- `test_assignee_starts_task` (`assigned` → `in_progress`, `started_at` set)
- `test_starting_twice_returns_conflict`
- `test_progress_is_clamped_to_zero_to_one_hundred`
- `test_non_assignee_cannot_update_progress`
- `test_cancelled_task_cannot_be_started`

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=TaskProgressTest`
Expected: FAIL.

- [ ] **Step 3: Implement the state guards**

`StartTaskUseCase` finds the assignment for the employee; 404 if absent; 409 `STATE_NOT_TRANSITIONABLE` unless status is `assigned`; set `in_progress`, `started_at = now()`. `UpdateProgressUseCase` allows `assigned` or `in_progress` or `revision_required`; sets progress and checklist, and moves `assigned`/`revision_required` to `in_progress`.

Use `ApiException` subclasses or `ApiResponse::error` consistently. Use HTTP 409 for state conflicts.

- [ ] **Step 4: Add routes**

```php
Route::post('/tasks/{id}/start', [TaskController::class, 'start'])->whereNumber('id');
Route::patch('/tasks/{id}/progress', [TaskController::class, 'progress'])->whereNumber('id');
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=TaskProgressTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Tasks routes/api/v1/app.php tests/Feature/Api/V1/App/TaskProgressTest.php
git commit -m "feat(tasks): add start and progress transitions"
```

---

### Task 4: Submission and review

**Files:**
- Create: `app/Modules/Tasks/Application/SubmitTask/SubmitTaskUseCase.php`
- Create: `app/Modules/Tasks/Application/ReviewSubmission/ReviewSubmissionUseCase.php`
- Modify: `app/Modules/Tasks/Http/Controllers/TaskController.php`
- Create: `app/Modules/Tasks/Http/Controllers/HrTaskSubmissionController.php`
- Modify: `routes/api/v1/app.php`
- Modify: `routes/api/v1/admin.php`
- Test: `tests/Feature/Api/V1/App/TaskSubmissionTest.php`
- Test: `tests/Feature/Api/V1/Hr/ReviewTaskSubmissionTest.php`

**Interfaces:**
- Produces: `SubmitTaskUseCase::execute(int $taskId, int $employeeId, ?string $note, array $checklist, array $evidenceFiles, array $attachmentIds): Task`; `ReviewSubmissionUseCase::execute(int $submissionId, int $reviewerUserId, string $role, string $decision, ?int $rating, ?string $note): Task`.

- [ ] **Step 1: Write failing tests**

Employee side:
- `test_assignee_submits_task_for_review`
- `test_submission_requires_evidence_when_task_requires_photo_proof`
- `test_second_pending_submission_is_rejected`
- `test_revision_submission_returns_to_under_review`

HR side:
- `test_reviewer_approves_submission_with_rating`
- `test_review_requires_note_for_revision`
- `test_reviewer_cannot_review_twice`
- `test_task_closes_when_all_assignments_complete`

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=TaskSubmissionTest` and `php artisan test --filter=ReviewTaskSubmissionTest`
Expected: FAIL.

- [ ] **Step 3: Implement submission**

Only the assignee may submit; allowed assignment states are `in_progress` and `revision_required`. Reject if a `pending` submission already exists. Accept multipart `evidence[]` files and/or existing `attachment_ids`. If `tasks.requires_photo_proof` and neither source supplies an evidence file, return 422 `EVIDENCE_REQUIRED`. Create `task_submissions`, store new evidence files under `tasks/{taskId}/submissions/{submissionId}`, create `task_attachments` rows with `kind = evidence`, link any pre-uploaded evidence ids, set assignment to `submitted` (or `under_review`). Keep one pending submission per assignment enforced by the partial unique index plus a transaction.

- [ ] **Step 4: Implement review**

Load submission with assignment.task. Authorize creator or HR/admin. Only `pending` submissions can be reviewed. `approved` requires rating `1..5`; sets submission `approved`, assignment `completed`, `completed_at`; closes the task when every assignment is `completed`/`cancelled`. `revision_requested` requires a non-empty note; sets submission `revision_requested`, assignment `revision_required`. Return the updated task/assignment.

- [ ] **Step 5: Add routes and run tests**

```php
// routes/api/v1/app.php
Route::post('/tasks/{id}/submissions', [TaskController::class, 'submit'])->whereNumber('id');

// routes/api/v1/admin.php (already inside the hr-prefixed group)
Route::post('task-submissions/{id}/review', [HrTaskSubmissionController::class, 'review'])->whereNumber('id');
```

Run: `php artisan test --filter=TaskSubmissionTest` and `php artisan test --filter=ReviewTaskSubmissionTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Tasks routes tests
git commit -m "feat(tasks): add submissions and per-assignment review"
```

---

### Task 5: Comments

**Files:**
- Create: `app/Modules/Tasks/Http/Controllers/TaskCommentController.php`
- Modify: `routes/api/v1/app.php`
- Test: `tests/Feature/Api/V1/App/TaskCommentTest.php`

**Interfaces:**
- Produces: `POST /api/v1/tasks/{id}/comments` with `body` max 2000; returns the created comment.

- [ ] **Step 1: Write failing tests**

Tests:
- `test_assignee_and_creator_can_comment`
- `test_unrelated_employee_cannot_comment`
- `test_comment_body_is_required_and_limited_to_2000`

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=TaskCommentTest`
Expected: FAIL.

- [ ] **Step 3: Implement comment access and creation**

Reuse `TaskAccess::canView`. Only assignee, creator, or HR/admin may comment. Store `author_user_id` from the authenticated user. Return 201 with `id`, `author_user_id`, `body`, `created_at`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=TaskCommentTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Tasks routes/api/v1/app.php tests/Feature/Api/V1/App/TaskCommentTest.php
git commit -m "feat(tasks): add task comments"
```

---

### Task 6: Private attachments

**Files:**
- Create: `app/Modules/Tasks/Http/Controllers/TaskAttachmentController.php`
- Modify: `routes/api/v1/app.php`
- Test: `tests/Feature/Api/V1/App/TaskAttachmentTest.php`

**Interfaces:**
- Consumes: Laravel `UploadedFile`; `Storage::disk('local')` rooted at `storage/app/private`.
- Produces: `POST /api/v1/tasks/{id}/attachments` multipart with `file` and `kind` (`brief`/`evidence`); returns metadata. Evidence must include `submission_id`.

- [ ] **Step 1: Write failing tests**

Tests:
- `test_assignee_uploads_private_brief_attachment`
- `test_evidence_can_be_added_to_an_existing_submission`
- `test_attachment_rejects_disallowed_mime_type`
- `test_attachment_rejects_files_over_ten_megabytes`
- `test_evidence_requires_a_submission_id`
- `test_unrelated_employee_cannot_upload`

Use `UploadedFile::fake()` and `Storage::fake('local')`.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=TaskAttachmentTest`
Expected: FAIL.

- [ ] **Step 3: Implement upload**

Validate `file` with `mimes:jpg,jpeg,png,pdf,docx,xlsx` and `max:10240`; `kind` required in `brief,evidence`; `submission_id` required when `kind = evidence` and must belong to the task. Store on `local` disk under `tasks/{taskId}`; compute `sha256`; create metadata row with `storage_disk = local`. Return 201. Do not expose a public URL; return `id`, `original_name`, `mime_type`, `size_bytes`, `kind`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=TaskAttachmentTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Tasks routes/api/v1/app.php tests/Feature/Api/V1/App/TaskAttachmentTest.php
git commit -m "feat(tasks): add private task attachments"
```

---

### Task 7: Cancellation, authorization regressions, and PostgreSQL verification

**Files:**
- Modify: `app/Modules/Tasks/Http/Controllers/HrTaskController.php`
- Modify: `app/Modules/Tasks/Application/Support/TaskAccess.php`
- Modify: `routes/api/v1/admin.php`
- Test: `tests/Feature/Api/V1/Hr/CancelTaskTest.php`
- Modify: `docs/roadmap.md`

- [ ] **Step 1: Write failing tests**

Tests:
- `test_creator_can_cancel_assigned_task`
- `test_cancelled_task_rejects_start_progress_and_submit`
- `test_hr_cannot_cancel_another_hrs_task` (unless admin)
- `test_employee_cannot_cancel_task`

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=CancelTaskTest`
Expected: FAIL.

- [ ] **Step 3: Implement cancellation**

Add `Route::post('tasks/{id}/cancel', [HrTaskController::class, 'cancel'])->whereNumber('id');` to the HR route group. Allow the creator or an `admin` to cancel a task that is `draft` or `assigned`. Set `tasks.status = cancelled`, `cancelled_at = now()`, and set all non-terminal assignments to `cancelled`. Return 409 for tasks already `closed`/`cancelled`.

- [ ] **Step 4: Run the full suite on SQLite and PostgreSQL**

Run:
```bash
php artisan test
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5433 DB_DATABASE=hadirmetrik_test DB_USERNAME=hadirmetrik DB_PASSWORD=9090 php artisan test
```
Expected: all tests pass on both connections.

- [ ] **Step 5: Update the roadmap**

Mark Phase 5 backend items complete with the actual test count and note that the Flutter integration is the next slice. Do not mark face recognition production-ready.

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Tasks tests docs/roadmap.md
git commit -m "feat(tasks): cancel tasks and verify phase 5 backend"
```

---

### Task 8: Update status documentation

**Files:**
- Modify: `docs/roadmap.md`

- [ ] **Step 1: Mark Phase 2 as complete**

Tick every Phase 2 checklist item, since the user confirmed employee profile, role gates, HR employee endpoints, and account lifecycle are implemented.

- [ ] **Step 2: Correct Phase 4 status**

Tick `retry otomatis dengan backoff` and add a note that the Flutter client now has capped backoff, per-event errors, causal ordering, and a stable per-install device id. Leave face inference/liveness and physical-device testing unchecked.

- [ ] **Step 3: Commit**

```bash
git add docs/roadmap.md
git commit -m "docs(roadmap): sync phase 2 and phase 4 status"
```
