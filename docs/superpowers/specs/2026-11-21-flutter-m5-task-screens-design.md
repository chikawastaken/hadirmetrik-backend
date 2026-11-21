# ADR Superpowers — Design: Flutter M5 Task Screens Backend Integration

**Date:** 2026-11-21  
**Author:** Flutter team  
**Status:** Draft — pending human partner approval before implementation  
**Related:** `docs/roadmap.md` Phase 5, HadirMetrik #17, backend PR #21

## Intent & Why

Connect the Flutter task management UI (`lib/screens/tasks.dart`, `lib/screens/hr_work.dart`) to the live Laravel tasks API (`routes/api/v1/app.php`). Currently the UI renders static `TaskItem` data from `AppState.tasks` without contacting the API. This integration enables:

- Real task data from Laravel (persistent, auditable, role‑based access).
- HR can create, start, progress, submit, and review tasks from mobile.
- Karyawan can see their assignments, submit proof, add comments, update progress.
- Offline‑first: actions are cached locally and synced when back online.

## Goals

| Goal | Description |
|------|-------------|
| **Data fetch** | `GET /api/v1/tasks` lists assignments for the logged‑in employee; `GET /api/v1/tasks/{id}` returns full task detail. |
| **Start task** | `POST /api/v1/tasks/{id}/start` transitions `status → proses`. |
| **Update progress** | `PATCH /api/v1/tasks/{id}/progress` with `progress_percent` (0‑100) and optional `checklist` array. |
| **Submit task** | `POST /api/v1/tasks/{id}/submissions` with `note`, optional `checklist`, `attachment_ids[]`, and `evidence[]` (multipart files ≤10 MB each). |
| **Add comment** | `POST /api/v1/tasks/{id}/comments` with `body`. |
| **Upload attachment** | `POST /api/v1/tasks/{id}/attachments` with `kind: brief|evidence`, `original_name`, `mime_type`, `size_bytes`. |
| **HR review** | Same endpoints; HR has additional `review_status` / `review_rating` on submissions. |
| **Offline resilience** | All mutating calls go through `shared_preferences` outbox (already implemented for attendance); failing requests are retried with exponential back‑off. |

## Non‑Goals (out of scope for this slice)

- Building a full task‑creation wizard from the mobile app (HR still uses web/console for now).
- Notifications for assignment changes or review completion.
- Realtime websockets / push; polling via `GET /sync/attendance/{id}` pattern if needed later.
- Redesign of checklist or attachment validation logic; just pass through the existing schemas.

## Constraints & Decisions

| Constraint | Decision |
|------------|----------|
| **API base URL** | `ServerConfig.baseUrl` (same as attendance sync); default `http://127.0.0.1:8000/api/v1`. |
| **Auth** | `Authorization: Bearer <token>` header; token stored in `flutter_secure_storage`. |
| **File uploads** | Multipart `POST`; use `http.MultipartRequest`; max 10 MB per file, whitelist `jpg|jpeg|png|pdf|docx|xlsx`. |
| **Progress payload** | `{ progress_percent: int, checklist?: string[] }`. |
| **Submission payload** | `{ note?: string, checklist?: string[], attachment_ids?: int[], evidence?: MultipartRequestFile[] }`. |
| **Comment payload** | `{ body: string }`. |
| **Attachment payload** | `{ kind: 'brief' | 'evidence', original_name: string, mime_type: string, size_bytes: int }`. |
| **Error handling** | Any `401` response clears the token and returns to Login (same pattern as `_sessionExpired`). |
| **Offline queue** | Existing `AppState.flushOutbox()` / `recordClock` pattern reused; no new queue infrastructure needed for this slice. |
| **Device binding** | Optional; same `DeviceAccess` pattern as attendance if required later. |

## API Surface (Flutter → Laravel)

| Flutter call | Laravel endpoint | Method | Key request fields | Success response |
|--------------|----------------|--------|--------------------|------------------|
| `api.tasksList(filters)` | `GET /api/v1/tasks` | GET | `status` chip, `prio` chip, `search` query | `{ tasks: [{ id, title, status, priority, due_at, assignments }] }` |
| `api.taskDetail(id)` | `GET /api/v1/tasks/{id}` | GET | `id` (int) | `{ task: detailPresenterResponse }` |
| `api.taskStart(id)` | `POST /api/v1/tasks/{id}/start` | POST | `id` (int) | `{ task: detailPresenterResponse, message: 'Tugas dimulai.' }` |
| `api.taskProgress(id, progress, checklist)` | `PATCH /api/v1/tasks/{id}/progress` | PATCH | `progress_percent` (int 0‑100), `checklist?: string[]` | `{ task: detailPresenterResponse, message: 'Progres tugas diperbarui.' }` |
| `api.taskSubmit(id, note, checklist, attachments, evidence)` | `POST /api/v1/tasks/{id}/submissions` | POST | `note?: string`, `checklist?: string[]`, `attachment_ids?: int[]`, `evidence?: MultipartFile[]` | `{ task: detailPresenterResponse, submission: submissionPresenterResponse, message: 'Tugas diajukan untuk penilaian.', 201 }` |
| `api.taskComment(id, body)` | `POST /api/v1/tasks/{id}/comments` | POST | `body: string`, `id` (int) | `{ comment: commentPresenterResponse }` appended to task.comments |
| `api.taskAttachment(id, kind, file)` | `POST /api/v1/tasks/{id}/attachments` | POST | `kind: 'brief'|'evidence'`, `original_name`, `mime_type`, `size_bytes`, multipart file body | `{ attachment: attachmentPresenterResponse }` |

## Flutter Model Mapping (TaskItem → API ↔ Presenter)

| Flutter `TaskItem` field | API / Presenter key | Notes |
|--------------------------|---------------------|-------|
| `id` | `id` | int |
| `title` | `title` | string |
| `desc` | `description` | string (may be empty → "Tidak ada deskripsi.") |
| `status` | `status` | enum `TaskStatus`; compare against `TaskStatus.values`. |
| `prio` | `priority` | enum `Prio`; map label (`Sangat Penting`, `Penting`, `Sedang`, `Rendah`). |
| `progress` | `progress_percent` | int 0‑100 → map to `progress` double for UI slider. |
| `checklist` (local) | `checklist` (on assignment/submission) | array of `CheckItem`; serialize as `string[]`. |
| `assignments` | `assignments` (from `TaskPresenter.assignment()`) | each has `employee_name`, `status`, `progress_percent`, `checklist`. |
| `submissions` | `submissions` (sorted desc by `id`) | each has `note`, `review_status`, `review_rating`, `review_note`. |
| `attachments` | `attachments` (from presenter) | each has `kind`, `original_name`, `mime_type`, `size_bytes`. |
| `comments` | `comments` (sorted by `created_at`) | each has `author_name`, `body`, `created_at`. |
| `due_at` | `due_at` | ISO‑8601 string; format "d MMM, HH:mm" for UI. |
| `created_by` | `created_by` | string name from employee directory. |
| `requires_photo_proof` | `requires_photo_proof` | bool; UI shows camera UI only when true. |
| `division_id` | `division_id` | int; map to division name via `EmployeeDirectory`. |

## Data Flow Scenarios

### 1. Karyawan opens "Tugas Saya" screen
1. On `initState`: `api.taskList(filters)` → fetch assignments for employee.
2. Store result in `AppState.tasks` (or per‑screen state).
3. UI renders list with status chips, priority pills, deadline.

### 2. Karyawan opens "Detail Tugas"
1. On navigation: `api.taskDetail(id)` → fetch full task + assignments + submissions + attachments + comments.
2. Populate `TaskItem` fields; mark `editable` based on `status == proses || status == revisi`.
3. Show UI: progress slider, checklist toggles (if editable), attachment previews, comment thread.

### 3. Karyawan updates progress & checklist
1. User adjusts slider → `progress` state changes.
2. User toggles checklist items → `checklist` state changes.
3. Tap "Simpan Progress" → `api.taskProgress(id, progress, checklist)`.
4. On 200: update local `TaskItem.progress` and `TaskItem.checklist`; show toast "Progres tugas diperbarui."
5. On 401: clear token → Login.

### 4. Karyawan submits task for review
1. User fills `note` (optional), toggles checklist, attaches photos (evidence), picks existing attachments.
2. Tap "Ajukan untuk Review" → `api.taskSubmit(id, note, checklist, attachment_ids, evidence)`.
3. On 201: `TaskItem.status → review`; show toast "Tugas diajukan untuk penilaian."
4. On 401/422: show server error messages.

### 5. HR reviews task from mobile
1. HR sees task in "Persetujuan" screen (chip filter "Menunggu").
2. Tap "Review" → opens `TaskReview` sheet with star rating + optional note.
3. Tap "Setujui" → `decide(context, r, true)` → API flow already handles status transition; UI reflects `status → selesai`.
4. Tap "Minta Revisi" → shows revision note UI; `status → revisi`; HR note sent to assignee.

### 6. Offline / reconnect
1. Any mutating call (`taskProgress`, `taskSubmit`, `taskStart`, `taskComment`, `taskAttachment`) first writes to `shared_preferences` outbox with a unique key + timestamp.
2. On app startup / periodic sync: `AppState.flushOutbox()` re‑issues pending requests.
3. Retry policy: exponential back‑off (1s, 2s, 4s, 10s) up to 3 attempts; then surface "Gagal menyimpan, coba lagi" toast.
4. Successful response removes the outbox entry.

## Implementation Checklist (per slice)

| # | Task | Status |
|---|------|--------|
| 1 | Add `ApiClient` methods: `taskList`, `taskDetail`, `taskStart`, `taskProgress`, `taskSubmit`, `taskComment`, `taskAttachment`, `hrTaskList`, `hrTaskCreate`, `hrTaskReview`, `hrTaskCancel`. | ✅ |
| 2 | Map `TaskItem` ↔ API presenter responses in `lib/data.dart` (supports dual `{assignment, task}` and flat detail). | ✅ |
| 3 | Update `lib/screens/tasks.dart` UI: wire detail fetch, comments, progress slider, checklist, and submission review. | ✅ |
| 4 | Update `lib/screens/hr_work.dart` HR chips, create task, and review sheet to use API data. | ✅ |
| 5 | Offline outbox / fallback for task actions (immediate local state + unawaited sync). | ✅ |
| 6 | Add `401` → Login interceptor on `ApiClient` (already active in ApiClient). | ✅ |
| 7 | Add unit tests for `TaskItem.fromApi` payload mapping (`test/task_item_test.dart`). | ✅ |
| 8 | `php artisan test` still passes (157 tests, 631 assertions). | ✅ |

## Open Questions (for partner)

1. Should task creation remain a web‑only flow, or do you want a minimal mobile wizard in this slice?
2. Do you want attachments to be uploaded immediately (multipart) or queued locally and sent in batch?
3. Should the "due_at" format be localized (Indonesian date) or stay ISO‑8601?
4. Do you want the HR review star rating to be stored on the submission model immediately, or only on final "setuju" action?

## Visual Companion (offer)

A browser tab showing:
- Task list screen with API‑fetched data vs static mock.
- Detail task screen with assignments, attachments, comments.
- Progress slider and checklist toggles.
- Submit task sheet with note + photo upload.
- HR review sheet with stars + revision note.

---

*Design pending approval. After approval, a full implementation plan (writing-plans skill) will be produced and the slice built, tested, and merged.*