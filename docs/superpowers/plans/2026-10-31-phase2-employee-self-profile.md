# Phase 2 Employee Self-Profile Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete Phase 2 by adding employee self-profile endpoints (GET/PATCH /api/v1/me/profile) in Laravel backend and integrating Flutter mobile app to fetch/display real profile data instead of hardcoded placeholder.

**Architecture:** Extend existing modular monolith. Add `EmployeeProfileController` in `Employees` module (read/write own profile), wire routes in `routes/api/v1/app.php` with `auth:sanctum` + `password.changed` middleware. Flutter fetches on app start/refresh and stores in `AppState`.

**Tech Stack:** Laravel 13.x, PHP 8.4+, Sanctum, Flutter 3.27+, Dart 3.6+, existing `ApiClient` + `AppState` pattern.

**Spec:** `docs/roadmap.md` §5 (Phase 2), `docs/architecture.md` §3.3 (module structure), `docs/openapi/auth.yaml` (response envelope pattern).

## Global Constraints

- All API responses use `App\Shared\Http\ApiResponse` envelope (`success`/`message`/`data`/`errors`).
- Endpoints under `/api/v1` prefix, protected by `auth:sanctum` and `password.changed` middleware.
- Cross-module access only via Contracts (DTOs), never Eloquent models directly.
- Employee can only read/update their own profile (object-level authorization via `user_id`).
- Updatable fields: `phone_e164` (nullable, E.164), `job_title` (nullable), `division_id` (nullable, must exist and be active), `work_email` (unique, lowercase). `employee_number`, `full_name`, `employment_start`, `employment_status`, `face_registered_at`, `manager_id` are **read-only** (HR-only).
- Validation: `phone_e164` regex `^\+[1-9]\d{1,14}$`, `work_email` unique on `employees` table excluding self, `division_id` exists in `divisions` with `is_active=true`.
- Flutter secure storage for token; `AppState` holds `EmployeeProfile` model; screens read from state.
- Tests: backend feature tests (`tests/Feature/Api/V1/App/EmployeeProfileTest.php`); Flutter widget/integration tests optional but encouraged.

## Review Focus

1. **Unauthenticated access** → must return 401 (middleware handles; test asserts envelope shape).
2. **Cross-employee access** → user A cannot read/update user B's profile (guard via `user_id` in controller; test asserts 403/404).
3. **Invalid division_id** → inactive or non-existent division rejected with 422 (validation test).
4. **Duplicate work_email** → another employee's email rejected with 422 (validation test).
5. **Stale local cache** → Flutter must refetch on app resume/token change (integration test or manual verify).

---

### Task 1: Backend — EmployeeProfileController (Read)

**Files:**
- Create: `app/Modules/Employees/Http/Controllers/EmployeeProfileController.php`
- Modify: `routes/api/v1/app.php` (add GET route)
- Test: `tests/Feature/Api/V1/App/EmployeeProfileTest.php`

**Interfaces:**
- Consumes: `EmployeeDirectory::findByUserId(int $userId): ?EmployeeSummary` (existing)
- Produces: `GET /api/v1/me/profile` → `ApiResponse` with `EmployeeProfileData` (extends `EmployeeSummary` with compensation, shift, location, manager, invitation)

- [ ] **Step 1: Write failing test for GET /api/v1/me/profile**

```php
// tests/Feature/Api/V1/App/EmployeeProfileTest.php
public function test_get_own_profile_returns_full_data(): void
{
    $employee = EmployeeFactory::create(['user_id' => $user->id]);
    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/me/profile');
    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.employee.id', $employee->id)
        ->assertJsonPath('data.employee.employee_number', $employee->employee_number)
        ->assertJsonPath('data.employee.full_name', $employee->full_name)
        ->assertJsonPath('data.compensation.base_salary', '...')
        ->assertJsonPath('data.shift_assignment.shift_id', ...)
        ->assertJsonPath('data.work_location_assignment.work_location_id', ...);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Api/V1/App/EmployeeProfileTest.php --filter=test_get_own_profile_returns_full_data`
Expected: FAIL (route/controller not found)

- [ ] **Step 3: Create EmployeeProfileController with __invoke for GET**

File: `app/Modules/Employees/Http/Controllers/EmployeeProfileController.php`
- Inject `GetEmployeeDetailUseCase` (existing) or create `GetOwnProfileUseCase` that reuses same logic filtered by authenticated user's `user_id`.
- Return `ApiResponse::success([...])` matching `AuthPayload::employee()` shape plus `compensation`, `shift_assignment`, `work_location_assignment`, `manager`, `invitation`.

- [ ] **Step 4: Add route in routes/api/v1/app.php**

```php
Route::middleware(['auth:sanctum', 'password.changed'])->group(function (): void {
    Route::get('/me/profile', [\App\Modules\Employees\Http\Controllers\EmployeeProfileController::class, 'show']);
});
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/Api/V1/App/EmployeeProfileTest.php --filter=test_get_own_profile_returns_full_data`
Expected: PASS

- [ ] **Step 6: Add test for unauthorized access (401)**

```php
public function test_get_profile_requires_auth(): void
{
    $this->getJson('/api/v1/me/profile')->assertUnauthorized();
}
```

- [ ] **Step 7: Run and commit**

```bash
git add app/Modules/Employees/Http/Controllers/EmployeeProfileController.php routes/api/v1/app.php tests/Feature/Api/V1/App/EmployeeProfileTest.php
git commit -m "feat(employees): add GET /api/v1/me/profile endpoint"
```

---

### Task 2: Backend — EmployeeProfileController (Update)

**Files:**
- Modify: `app/Modules/Employees/Http/Controllers/EmployeeProfileController.php` (add `update` method)
- Create: `app/Modules/Employees/Http/Requests/UpdateEmployeeProfileRequest.php`
- Modify: `routes/api/v1/app.php` (add PATCH route)
- Test: `tests/Feature/Api/V1/App/EmployeeProfileTest.php` (add update tests)

**Interfaces:**
- Consumes: `EmployeeUpdater` contract (new) or direct Eloquent in controller with validation
- Produces: `PATCH /api/v1/me/profile` → `ApiResponse` with updated `EmployeeProfileData`

- [ ] **Step 1: Write failing tests for PATCH /api/v1/me/profile**

```php
public function test_update_own_profile_phone_and_job_title(): void
{
    $employee = EmployeeFactory::create(['user_id' => $user->id, 'phone_e164' => null, 'job_title' => null]);
    $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/me/profile', [
        'phone_e164' => '+6281234567890',
        'job_title' => 'Senior Staff',
    ]);
    $response->assertOk()
        ->assertJsonPath('data.employee.phone_e164', '+6281234567890')
        ->assertJsonPath('data.employee.job_title', 'Senior Staff');
    $this->assertDatabaseHas('employees', ['id' => $employee->id, 'phone_e164' => '+6281234567890', 'job_title' => 'Senior Staff']);
}

public function test_update_profile_rejects_invalid_phone(): void
{
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/me/profile', ['phone_e164' => 'invalid'])
        ->assertUnprocessable()->assertJsonPath('errors.phone_e164.0', 'Format telepon tidak valid (E.164).');
}

public function test_update_profile_rejects_duplicate_work_email(): void
{
    $other = EmployeeFactory::create(['work_email' => 'other@example.com']);
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/me/profile', ['work_email' => 'other@example.com'])
        ->assertUnprocessable()->assertJsonPath('errors.work_email.0', 'Email sudah digunakan karyawan lain.');
}

public function test_update_profile_rejects_inactive_division(): void
{
    $inactiveDivision = DivisionFactory::create(['is_active' => false]);
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/me/profile', ['division_id' => $inactiveDivision->id])
        ->assertUnprocessable()->assertJsonPath('errors.division_id.0', 'Divisi tidak aktif atau tidak ditemukan.');
}

public function test_cannot_update_other_employee_profile(): void
{
    $otherUser = UserFactory::create();
    $otherEmployee = EmployeeFactory::create(['user_id' => $otherUser->id]);
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/me/profile', ['job_title' => 'Hacker'])
        ->assertOk(); // own profile updated
    $this->assertDatabaseMissing('employees', ['id' => $otherEmployee->id, 'job_title' => 'Hacker']);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Api/V1/App/EmployeeProfileTest.php --filter=test_update_`
Expected: FAIL (route/controller method not found, validation not exist)

- [ ] **Step 3: Create UpdateEmployeeProfileRequest**

File: `app/Modules/Employees/Http/Requests/UpdateEmployeeProfileRequest.php`
- Rules: `phone_e164` nullable|regex:/^\+[1-9]\d{1,14}$/, `work_email` nullable|email|lowercase|unique:employees,work_email,{{auth()->user()->employee->id}},id, `division_id` nullable|exists:divisions,id,is_active,1, `job_title` nullable|string|max:100
- Forbid: `employee_number`, `full_name`, `employment_start`, `employment_status`, `face_registered_at`, `manager_id`, `user_id`, `id`

- [ ] **Step 4: Add update method to EmployeeProfileController**

```php
public function update(UpdateEmployeeProfileRequest $request): ApiResponse
{
    $employee = $this->directory->findByUserId($request->user()->getAuthIdentifier());
    // update allowed fields on $employee->fill($request->validated())->save()
    // reload with relations
    return ApiResponse::success($this->buildProfileData($employee));
}
```

- [ ] **Step 5: Add PATCH route in routes/api/v1/app.php**

```php
Route::patch('/me/profile', [\App\Modules\Employees\Http\Controllers\EmployeeProfileController::class, 'update']);
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Api/V1/App/EmployeeProfileTest.php`
Expected: PASS (all GET + PATCH tests)

- [ ] **Step 7: Run full test suite and commit**

```bash
php artisan test
git add app/Modules/Employees/Http/Controllers/EmployeeProfileController.php app/Modules/Employees/Http/Requests/UpdateEmployeeProfileRequest.php routes/api/v1/app.php tests/Feature/Api/V1/App/EmployeeProfileTest.php
git commit -m "feat(employees): add PATCH /api/v1/me/profile with validation"
```

---

### Task 3: Flutter — API Client & Models

**Files:**
- Modify: `lib/api.dart` (add `getProfile()`, `updateProfile()`)
- Create: `lib/models/employee_profile.dart` (EmployeeProfile, Compensation, ShiftAssignment, WorkLocationAssignment, Manager, InvitationSummary)
- Modify: `lib/state.dart` (add `EmployeeProfile? profile` field + `fetchProfile()` method)

**Interfaces:**
- Consumes: `ApiClient.get(String path)`, `ApiClient.patch(String path, Map body)`
- Produces: `Future<EmployeeProfile> getProfile()`, `Future<EmployeeProfile> updateProfile(Map<String, dynamic> data)`

- [ ] **Step 1: Create EmployeeProfile model**

File: `lib/models/employee_profile.dart`
- Parse JSON from `/me/profile` response: `employee` (id, employee_number, full_name, work_email, phone_e164, job_title, division{id,name}, employment_status, face_registered_at), `compensation` (base_salary, transport_allowance, meal_allowance, total_monthly, currency), `shift_assignment` (shift_id, name, is_overnight, effective_from), `work_location_assignment` (work_location_id, name, effective_from), `manager` (id, full_name, employee_number), `invitation` (id, status, requested_channels, expires_at, deliveries)

- [ ] **Step 2: Add API methods in api.dart**

```dart
static Future<EmployeeProfile> getProfile() async {
  final res = await _dio.get('/me/profile');
  return EmployeeProfile.fromJson(res.data['data']);
}

static Future<EmployeeProfile> updateProfile(Map<String, dynamic> data) async {
  final res = await _dio.patch('/me/profile', data: data);
  return EmployeeProfile.fromJson(res.data['data']);
}
```

- [ ] **Step 3: Update AppState in state.dart**

Add `EmployeeProfile? profile;` and `Future<void> fetchProfile() async { profile = await Api.getProfile(); notifyListeners(); }`
Call `fetchProfile()` in `AppState.initialize()` after token restored and in `login()`/`logout()`.

- [ ] **Step 4: Test manually with backend running**

Run Flutter app, login, verify profile screen shows real data (not `rina`).

- [ ] **Step 5: Commit**

```bash
cd /Users/mac/Dev/HadirMetrik
git add lib/models/employee_profile.dart lib/api.dart lib/state.dart
git commit -m "feat(mobile): add EmployeeProfile model, API methods, AppState integration"
```

---

### Task 4: Flutter — Profile Screen Uses Real Data

**Files:**
- Modify: `lib/screens/me.dart` (replace `rina` references with `app.profile`)
- Modify: `lib/screens/home.dart` (replace `rina` references in greeting, pay preview)

**Interfaces:**
- Consumes: `AppState.profile` (EmployeeProfile)
- Produces: UI showing real employee data

- [ ] **Step 1: Update Profile screen (me.dart)**

- Replace `Avatar(rina.initials...)` → `Avatar(app.profile?.employee.initials ?? '?')`
- Replace `Text(rina.name)` → `Text(app.profile?.employee.fullName ?? '-')`
- Replace `Text('${rina.role} · ${rina.division}')` → `Text('${app.profile?.employee.jobTitle ?? '-'} · ${app.profile?.employee.division?.name ?? '-'}')`
- Replace `Pill(rina.id...)` → `Pill(app.profile?.employee.employeeNumber ?? '-')`
- Update "PERANGKAT & KERJA" section: shift from `app.profile?.shiftAssignment?.name`, location from `app.profile?.workLocationAssignment?.name`, manager from `app.profile?.manager?.fullName`, rekening from compensation/bank info (TBD if not in profile yet).
- Update "DATA WAJIB" section: face registered from `app.profile?.employee.faceRegisteredAt`.

- [ ] **Step 2: Update Home screen (home.dart)**

- Replace `Text('Selamat ${_greeting(now.hour)}, Rina')` → `Text('Selamat ${_greeting(now.hour)}, ${app.profile?.employee.firstName ?? 'Karyawan'}')`
- Update `_PayPreview` to use `app.profile?.compensation` for earnings preview.

- [ ] **Step 3: Update Pay screen (me.dart Pay class)**

- Replace `rina.name`, `rina.id`, `rina.division`, `rina.role` with `app.profile?.employee...`
- Bank account: add to profile model if needed (future) or keep placeholder.

- [ ] **Step 4: Test manually**

Run app, login as different employees, verify each sees own data.

- [ ] **Step 5: Commit**

```bash
cd /Users/mac/Dev/HadirMetrik
git add lib/screens/me.dart lib/screens/home.dart
git commit -m "feat(mobile): profile & home screens use real API data"
```

---

### Task 5: Backend — OpenAPI Documentation Update

**Files:**
- Modify: `docs/openapi/auth.yaml` (add `/me/profile` GET/PATCH schemas)

**Interfaces:**
- Produces: OpenAPI 3.1 schemas for `EmployeeProfileResponse`, `UpdateEmployeeProfileRequest`, error examples

- [ ] **Step 1: Add schemas and paths to auth.yaml**

```yaml
# Add to components/schemas
EmployeeProfileResponse:
  type: object
  properties:
    employee:
      $ref: '#/components/schemas/EmployeeDetail'
    compensation:
      $ref: '#/components/schemas/CompensationSummary'
    shift_assignment:
      $ref: '#/components/schemas/ShiftAssignmentSummary'
    work_location_assignment:
      $ref: '#/components/schemas/WorkLocationAssignmentSummary'
    manager:
      $ref: '#/components/schemas/ManagerSummary'
    invitation:
      $ref: '#/components/schemas/InvitationSummary'

# Add paths /api/v1/me/profile GET and PATCH with request/response
```

- [ ] **Step 2: Validate OpenAPI spec**

Run: `npx @redocly/openapi-cli lint docs/openapi/auth.yaml` (if available) or manual review.

- [ ] **Step 3: Commit**

```bash
git add docs/openapi/auth.yaml
git commit -m "docs(openapi): add /me/profile GET/PATCH schemas"
```

---

### Task 6: Integration Verification

**Files:** (no new files)

- [ ] **Step 1: Run full backend test suite**

Run: `cd /Users/mac/Dev/hadirmetrik-backend && php artisan test`
Expected: All tests pass (including new EmployeeProfileTest)

- [ ] **Step 2: Run Flutter analyze**

Run: `cd /Users/mac/Dev/HadirMetrik && flutter analyze`
Expected: No errors

- [ ] **Step 3: Manual end-to-end test**

1. Start Laravel: `php artisan serve`
2. Start Flutter: `flutter run -d chrome` (or device)
3. Register/login as employee created by HR
4. Verify Profile screen shows correct name, employee number, division, job title
5. Verify Home screen greeting uses first name
6. Verify Pay screen shows employee's compensation data
7. Edit phone/job title in Profile → PATCH succeeds → GET reflects change
8. Login as different employee → sees their own data

- [ ] **Step 4: Commit any fixes**

```bash
git commit -am "fix: integration adjustments"
```

- [ ] **Step 5: Tag Phase 2 complete**

```bash
git tag phase-2-complete
```

---

## Summary

| Task | Deliverable | Est. Effort |
|------|-------------|-------------|
| 1 | GET /api/v1/me/profile | 1.5h |
| 2 | PATCH /api/v1/me/profile + validation | 2h |
| 3 | Flutter API client, models, AppState | 2h |
| 4 | Flutter screens use real data | 1.5h |
| 5 | OpenAPI docs | 0.5h |
| 6 | Integration verification | 1h |
| **Total** | | **~8.5h** |

This completes Phase 2 per roadmap Definition of Done: *"Akun pengguna terhubung ke satu data karyawan yang valid; akun nonaktif tidak dapat mengakses fitur terlindungi; kontrol akses diuji di sisi API."*