# Longlivy API

Base URL: `https://longlivy.digitallhut.com/api/v1`

Every response uses the envelope `{ success, message, data }`. Errors use
`{ success: false, message, errors? }` with the usual Laravel validation
shape in `errors`. Authentication is a Sanctum bearer token returned by
`POST /auth/register` and `POST /auth/login` (`Authorization: Bearer <token>`).
Tokens expire after 30 days (`LONGLIVY_TOKEN_TTL_DAYS`); there is no refresh
endpoint — sign in again when a call returns 401.

## Corrections to the "Longlivy new api" document

1. Create Goal is `POST /goals`, not `POST /fields`.
2. `POST /meditation/sessions`: `title` and `template_id` are **optional**
   (201 without them). `template_id` must reference one of the user's own
   templates, otherwise 422 `"The selected template is not available."`.
3. Passwords are minimum **8** characters and must be confirmed
   (`password` + `password_confirmation`) on register and reset.
4. Targets created by `POST /energy/targets/calculate` have
   `source: "longlivy_calculated"`. Editing a target switches it to
   `"manual"`; later recalculations skip manual targets and report
   `reason: "manual_override"`.
5. `logged_at` on meals and weight logs should include a UTC offset
   (ISO 8601). A value without an offset is read as UTC. The calendar
   `date` field is derived in the user's profile timezone.
6. Meal recognition photo upload uses the field **`image`** (multipart
   file, jpeg/png/heic/heif/webp, max 8 MB), not `image_base64`.
   Recognition results are **estimates**; every item comes back with
   `estimated: true` and a `confidence` score and should be reviewed by
   the user before saving.
7. 5xx responses never include SQL text or connection details; they
   return `{"success":false,"message":"Server error."}`.

## Authentication

### `POST /auth/register`
`first_name`*, `last_name`*, `email`*, `password`* (min 8, confirmed),
plus optional profile fields (`date_of_birth`, `gender`, `height`,
`current_weight`, `timezone`, `activity_level`, ...). Returns 201 with
`user`, `token`, `token_type: "Bearer"`, `expires_at`.

### `POST /auth/login`
`email`, `password`. Returns 200 with the same shape; 401 for wrong
credentials. Throttled: 5 attempts/minute per email+IP.

### `POST /auth/forgot-password`
`{ email }`. Always returns 200 with
`"If an account exists for this email, a reset code has been sent."`
for known and unknown addresses alike (no account probing). Throttled:
3 sends per 15 minutes per email+IP.

### `POST /auth/reset-password`
`{ email, code, password, password_confirmation }`. The code is **6
digits**, single use, valid for **60 minutes**. A wrong or expired code
returns 422 `"This reset code is invalid or has expired."`. On success
the password is updated and **all existing Sanctum tokens are revoked**;
the user must sign in again. Response: 200 `"Password has been reset.
Please sign in."`

### `POST /auth/email/verify` (authenticated)
`{ code }` — 6 digits, valid for **30 minutes**, max **5 attempts** per
code (then the code is rejected until a new one is requested). A wrong
code returns 422 `"The code is invalid or has expired."`. On success the
user's `email_verified_at` is set. Verification is **optional** — no API
route currently requires a verified email.

### `POST /auth/email/resend` (authenticated)
Resends the verification code. Throttled: 1 request/minute per user
(429 when hit). Returns 409 `"Email is already verified."` if already
verified.

### `DELETE /auth/account` (authenticated)
`{ password, confirmation: "DELETE" }`. Both fields are required; the
confirmation string must be exactly `DELETE` (otherwise 422
`"Type DELETE to confirm."`), and the password must be the current one
(422 `"The password is incorrect."` otherwise). Throttled: 5
tries/minute per IP.

Deletion is **hard**: the user row and every related record (profile,
meals, activities, fastings, weight logs, goals, streaks, meditation
data, devices, notifications, tokens) are removed immediately and
permanently in one transaction. Nothing is kept. All tokens are
revoked, so the app is signed out on the next request. There is no
grace period and no export step — the client should offer export
before calling this.

### `POST /auth/logout` (authenticated)
Deletes the current token and all registered push devices for the
session. Returns 200.

### `GET /auth/me` (authenticated)
Returns the current user with profile.

## Activities

Types: `running`, `walking`, `cycling`, `hiking`, `jogging`, `other`.
Sources: `manual`, `tracked`, `imported`.

### `GET /activities`
Paginated `{ activities, meta }`. Filters: `from`, `to`, `type`.

### `POST /activities`
`client_id`* (client-generated idempotency key — re-posting the same
`client_id` returns **200** with the existing activity instead of
creating a duplicate), `type`*, `source`*, `started_at`*, `ended_at`*
(after `started_at`, max 24 h), `active_seconds`*, `paused_seconds`*,
`calories_kcal` (**nullable** — omit or send `null` when the calorie
value is unknown, e.g. no body weight configured), `calculation_method`*
(met, met_gps, calculated, manual, wearable — the full list is not
fixed), `distance_meters` (0–500000), `avg_heart_rate`, `steps`,
`route`, `notes`. Returns 201.

`route` is an optional object:

```json
{
  "route": {
    "encoding": "polyline5",
    "points": "kx~lEncvpC??_gQ`Ce}@l@_MbAyT",
    "point_count": 42
  }
}
```

- `encoding`: `polyline5` (Google encoded polyline, precision 5) or
  `polyline6` (precision 6). `geojson` is reserved.
- `points`: the encoded string (max 1 MB). Raw coordinate arrays are
  rejected — encode the track client-side.
- `point_count`: number of decoded points, for sanity checks.

The route round-trips: `GET /activities/{id}` returns the same
`route.encoding`, `route.points` and `route.point_count`.

### `GET /activities/{id}` — `{ activity }` (includes the route).

### `PUT /activities/{id}` — partial update: `type`, `distance_meters`,
`calories_kcal` (nullable), `avg_heart_rate`, `steps`, `notes`.

### `DELETE /activities/{id}` — 200.

## Notifications

### Item format

```json
{
  "id": "9b2f0000-...-000000000001",
  "type": "fasting_streak_reached",
  "title": "7-day fasting streak",
  "body": "You have completed 7 fasts in a row. Keep it going!",
  "data": { "streak": 7 },
  "read_at": null,
  "created_at": "2026-10-19T10:20:31+00:00"
}
```

`type` is one of the 15 keys below; `data` carries event-specific
payload (ids, numbers); `read_at` is `null` while unread.

### Which events generate notifications today

The backend currently emits these events (respecting the user's
`/notification-settings`):

| Event | Trigger |
|---|---|
| `fasting_begins` | `POST /fasting` starts a session |
| `fasting_ends` | `POST /fasting/{id}/end` completes a fast |
| `eating_phase_begins` | same completion (eating window open) |
| `fasting_streak_reached` | fasting streak hits a multiple of 7 days |

The other 11 types (`interim_goal_achieved`, `fasting_almost_over`,
`planned_fast_not_started`, `personal_best_achieved`, `activity_reminder`,
`activity_goal_achieved`, `calorie_goal_almost_reached`,
`calorie_target_exceeded`, `protein_goal_achieved`, `weight_reminder`,
`meditation_reminder`) are recognised and configurable but not yet
emitted; they are reserved for scheduled delivery.

### Push delivery

Devices register with `POST /devices` (`token`, `provider`
expo|apns|fcm, `platform`, `app_version`, `locale`, `timezone`; repeat
posts update the same record; `DELETE /devices/{token}` removes).

Push is delivered through the **Expo Push API** when
`LONGLIVY_PUSH_DRIVER=expo` (default `stub` logs only). Expo tokens
(`ExponentPushToken[...]`) need no backend credentials; APNs/FCM
delivery works once the platform credentials are configured on the Expo
push service. Notification delivery never fails a user request — push
errors are logged, not surfaced.

Test command: `php artisan longlivy:notify-test {email} --type=fasting_begins`
(`--ignore-settings` forces delivery when the user disabled the type).

### Routes

- `GET /notifications` — `{ notifications, unread_count, meta }`,
  `?unread_only=1`, paginated.
- `GET /notifications/{id}` — single notification (403 for another
  user's id).
- `POST /notifications/{id}/read` — mark one read (403 for another
  user's id).
- `POST /notifications/read-all` — `{ updated: n }`.
- `GET /notification-settings` — `{ settings: { 15 boolean keys } }`
  (defaults: everything on except `weight_reminder` and
  `meditation_reminder`).
- `PUT /notification-settings` — `{ settings: { key: bool } }`, partial
  updates allowed, unknown key → 422.

## Fasting plans

Accepted `method` values: `14:10`, `16:8`, `18:6`, `20:4`, `24h`,
`36h`, `48h`, `72h`, `96h`, `individual`.
Accepted `category` values: `intermittent`, `longer`, `individual`.

`weekdays` is stored and returned exactly as sent (ISO-8601 numbers,
Mon=1 … Sun=7). `PUT` is a partial update — send only the fields you
change (`{"active": false}` works). `fasting_hours`/`eating_hours` must
sum to 24 for `intermittent` methods.

Per-day overrides:

- `GET /fasting/plans/{id}/overrides`
- `PUT /fasting/plans/{id}/overrides/{YYYY-MM-DD}` — body: `fasting_hours`,
  `eating_hours`, `start_time`, `end_time`, `notes`
- `DELETE /fasting/plans/{id}/overrides/{YYYY-MM-DD}`

## Weight and targets

`POST /weight-logs` updates `profile.current_weight` and BMR follows.
`DELETE /weight-logs/{id}`: when the deleted log was the newest, the
**previous log's weight is restored** to the profile (and BMR follows).
If no logs remain, the profile keeps its last value — set it via
`PUT /profile` if needed.

Nutrition targets are **not** recalculated automatically after a weight
or profile change. Clients (or the backend on request) call
`POST /energy/targets/calculate` explicitly; manual targets are never
overwritten (see correction 4).

## Old meal timestamps

Meals saved before the timezone fix hold local time labelled as UTC.
One-off repair (run by the backend, not clients):

```
php artisan longlivy:fix-meal-timezones --before="2026-10-19 12:00:00" --dry-run
php artisan longlivy:fix-meal-timezones --before="2026-10-19 12:00:00"
```

`--before` (UTC) is the deployment moment of the fix; only meals
created before it are touched. The command subtracts the profile
timezone's offset from `logged_at` and recomputes `date`.

## Meditation

- `GET /meditation` — published catalog; `GET /meditation/{id}` —
  detail. `audio_url` is an absolute **https** URL built from
  `MEDITATION_AUDIO_BASE_URL` (no signing, no expiry);
  `thumbnail_url` from `MEDITATION_THUMBNAIL_BASE_URL`.
- Categories: `GET /meditation/categories` (admin: `POST`).
- Sessions: `GET/POST /meditation/sessions`, `GET/PUT/DELETE
  /meditation/sessions/{id}`, `.../pause`, `.../resume`, `.../complete`,
  `.../cancel`, `GET /meditation/sessions/active`. `title` and
  `template_id` are optional.
- Templates: `GET/POST /meditation/templates`, `PUT/DELETE
  /meditation/templates/{id}` — `name`, `duration_seconds`, `type`
  (guided|free|breathing), `breathing_enabled`,
  `closing_sound_enabled`, `background_sound`, `meditation_id` (must be
  a server meditation id). There is no endpoint to read a single
  template.
- Goals and reminders under `/meditation/goals` and
  `/meditation/reminders`.

## Food catalog

`GET /foods` — paged (`page`, `per_page`), `?q=` searches name/brand;
each food carries `name`, `brand`, `category_id`, `base_unit` (g|ml),
`base_amount`, full nutrients (`calories`, `protein`,
`carbohydrates`, `fat`, `fiber`, `sugar`, `saturated_fat`, `sodium`),
`grams_per_unit` or `serving_amount`/`ml_per_unit` for piece-based
foods, and `verified`. `GET /food-categories` lists categories. The
seeded catalog covers common staples (apple, rice, egg, chicken, ...);
apps no longer need to bundle their own starter foods.

## Meal recognition

`POST /nutrition/recognize` (throttled 20/hour/user):

```json
{ "type": "text", "text": "2 eggs and 100 g rice" }
```

or multipart with `image` for `type: "photo"`. Every draft item
includes `name`, `quantity`, `unit`, `food_id` (when matched),
`nutrients` (`calories`, `protein`, `carbohydrates`, `fat`, `fiber`)
scaled to the stated quantity, `confidence` and `estimated: true`.
Values are estimates and need user review (`needs_review`).
