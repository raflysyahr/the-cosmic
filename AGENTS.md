# AGENTS.md — Auth & Discuss Module
> Laravel Modular Monolith · Inertia React · Laravel Reverb
> Baca file ini seluruhnya sebelum mengerjakan task apapun.

---

## Konteks Project

Website web comic berbasis **Laravel + Inertia React**. Menambahkan dua module baru:
- **Auth** — autentikasi, registrasi, OAuth, session, profil
- **Discuss** — group chat real-time ala Telegram, custom emot, sistem pangkat berbasis XP

Semua kode module berada di `app/Modules/`. Tidak ada perubahan ke kode di luar folder tersebut kecuali `config/app.php`, `bootstrap/providers.php`, dan `routes/channels.php`.

---

## Aturan Wajib — Baca Sebelum Menulis Kode

### Arsitektur
- Semua module di `app/Modules/{NamaModule}/`
- Setiap module punya `ServiceProvider` sendiri yang mendaftarkan routes dan migrations
- **Tidak ada FK constraint** di level database antar module — relasi antar module hanya via plain `ULID` column
- **Tidak ada Eloquent relationship** (`hasMany`, `belongsTo`, dll) yang melintasi batas module
- Komunikasi antar module hanya lewat **Laravel Events** atau query langsung dengan `select` terbatas

### Kode
- Primary key semua tabel menggunakan **ULID** (`Str::ulid()`)
- Semua Model menggunakan `HasUlids` trait dari Laravel
- Gunakan **Enum PHP** (bukan string konstanta) untuk semua kolom `ENUM` database
- Semua business logic di **Service class** — Controller hanya memanggil Service
- Gunakan **Form Request** untuk semua validasi input
- Gunakan **DTO (Data class)** untuk transfer data antar layer
- Setiap fitur baru yang bersifat extensible wajib menggunakan kolom **JSON** yang sudah tersedia, bukan membuat kolom baru

### Extensible JSON Columns — Jangan Buat Kolom Baru
Kolom berikut sudah disiapkan untuk menyimpan fitur baru tanpa migration:

| Tabel | Kolom | Kegunaan |
|---|---|---|
| `users` | `preferences` | notif settings, theme, language |
| `user_profiles` | `preferences` | data profil extended |
| `discuss_rooms` | `settings` | slow mode, invite link, XP per pesan |
| `discuss_messages` | `metadata` | mentions, link preview, poll, forward |
| `discuss_ranks` | `perks` | emot eksklusif, warna nama, badge |
| `discuss_notifications` | `payload` | data konteks per tipe notif |

### Testing
- Setiap Service class wajib ada unit test
- Setiap endpoint HTTP wajib ada feature test
- Gunakan `RefreshDatabase` trait di semua test
- Gunakan factory untuk seed data test

---

## Struktur Database

### Module Auth

**`users`**
```
id (ULID, PK), username (VARCHAR 50, UNIQUE), email (VARCHAR 191, UNIQUE),
password (VARCHAR 255, nullable), display_name (VARCHAR 100), avatar_url (TEXT, nullable),
role (ENUM: reader|moderator|creator|admin), status (ENUM: active|suspended|banned),
email_verified_at (TIMESTAMP, nullable), last_login_at (TIMESTAMP, nullable),
preferences (JSON), created_at, updated_at
```

**`user_profiles`**
```
id (ULID, PK), user_id (ULID, UNIQUE), bio (TEXT, nullable),
website_url (VARCHAR 255, nullable), location (VARCHAR 100, nullable),
preferences (JSON), updated_at
```

**`user_social_accounts`**
```
id (ULID, PK), user_id (ULID), provider (VARCHAR 30),
provider_id (VARCHAR 100), access_token (TEXT), refresh_token (TEXT, nullable),
token_expires_at (TIMESTAMP, nullable), created_at
INDEX: UNIQUE(provider, provider_id)
```

**`auth_sessions`**
```
id (VARCHAR 100, PK), user_id (ULID, nullable),
ip_address (VARCHAR 45), user_agent (TEXT),
payload (LONGTEXT), last_activity (INT)
INDEX: INDEX(user_id), INDEX(last_activity)
```

**`password_reset_tokens`**
```
email (VARCHAR 191, PK), token (VARCHAR 255), created_at (TIMESTAMP)
```

### Module Discuss

**`discuss_rooms`**
```
id (ULID, PK), slug (VARCHAR 100, UNIQUE), name (VARCHAR 100),
description (TEXT, nullable), cover_url (TEXT, nullable),
type (ENUM: public|private|invite_only), owner_user_id (ULID),
context_type (VARCHAR 50, nullable), context_id (ULID, nullable),
is_active (BOOLEAN default true), settings (JSON), created_at, updated_at
INDEX: INDEX(context_type, context_id), INDEX(owner_user_id)
```

**`discuss_members`**
```
id (ULID, PK), room_id (ULID), user_id (ULID),
role (ENUM: member|moderator|admin), rank_id (ULID, nullable),
xp_points (INT default 0), muted_until (TIMESTAMP, nullable),
is_banned (BOOLEAN default false), last_read_at (TIMESTAMP, nullable), joined_at (TIMESTAMP)
INDEX: UNIQUE(room_id, user_id), INDEX(user_id), INDEX(rank_id)
```

**`discuss_messages`**
```
id (ULID, PK), room_id (ULID), user_id (ULID),
reply_to_id (ULID, nullable), type (ENUM: text|image|sticker|system),
body (TEXT, nullable), attachments (JSON), is_edited (BOOLEAN default false),
is_deleted (BOOLEAN default false), deleted_by_user_id (ULID, nullable),
metadata (JSON), created_at, updated_at
INDEX: INDEX(room_id, created_at), INDEX(user_id), INDEX(reply_to_id)
```

**`discuss_reactions`**
```
id (ULID, PK), message_id (ULID), user_id (ULID), emote_id (ULID), created_at
INDEX: UNIQUE(message_id, user_id, emote_id), INDEX(message_id)
```

**`discuss_emotes`**
```
id (ULID, PK), room_id (ULID, nullable), code (VARCHAR 50, UNIQUE),
name (VARCHAR 100), image_url (TEXT), is_animated (BOOLEAN default false),
is_active (BOOLEAN default true), uploaded_by_user_id (ULID), created_at
INDEX: INDEX(room_id)
```

**`discuss_ranks`**
```
id (ULID, PK), room_id (ULID, nullable), name (VARCHAR 50),
label_color (VARCHAR 7), icon_url (TEXT, nullable),
min_xp (INT default 0), order (INT default 1), perks (JSON), created_at
INDEX: INDEX(room_id, order)
```

**`discuss_notifications`**
```
id (ULID, PK), user_id (ULID), type (VARCHAR 50),
actor_user_id (ULID, nullable), payload (JSON),
is_read (BOOLEAN default false), created_at
INDEX: INDEX(user_id, is_read), INDEX(user_id, created_at)
```

---

## Struktur Folder Module

```
app/Modules/
├── Auth/
│   ├── AuthServiceProvider.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── LoginController.php
│   │   │   ├── RegisterController.php
│   │   │   ├── LogoutController.php
│   │   │   ├── PasswordResetController.php
│   │   │   ├── EmailVerificationController.php
│   │   │   └── SocialAuthController.php
│   │   ├── Requests/
│   │   │   ├── LoginRequest.php
│   │   │   ├── RegisterRequest.php
│   │   │   ├── PasswordResetRequest.php
│   │   │   └── UpdateProfileRequest.php
│   │   └── Middleware/
│   │       ├── EnsureEmailVerified.php
│   │       └── RedirectIfAuthenticated.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── UserProfile.php
│   │   ├── UserSocialAccount.php
│   │   └── AuthSession.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── SocialAuthService.php
│   │   ├── EmailVerificationService.php
│   │   └── PasswordResetService.php
│   ├── Events/
│   │   ├── UserRegistered.php
│   │   ├── UserLoggedIn.php
│   │   └── UserBanned.php
│   ├── Listeners/
│   │   ├── SendVerificationEmail.php
│   │   └── UpdateLastLogin.php
│   ├── Notifications/
│   │   ├── VerifyEmailNotification.php
│   │   └── PasswordResetNotification.php
│   ├── Enums/
│   │   ├── UserRole.php
│   │   └── UserStatus.php
│   ├── Data/
│   │   ├── RegisterData.php
│   │   └── UserData.php
│   ├── Contracts/
│   │   └── AuthServiceContract.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   │       └── AuthDatabaseSeeder.php
│   ├── routes/
│   │   └── auth.php
│   └── tests/
│       ├── Unit/
│       └── Feature/
│
└── Discuss/
    ├── DiscussServiceProvider.php
    ├── Http/
    │   ├── Controllers/
    │   │   ├── RoomController.php
    │   │   ├── MemberController.php
    │   │   ├── MessageController.php
    │   │   ├── ReactionController.php
    │   │   ├── EmoteController.php
    │   │   ├── RankController.php
    │   │   └── NotificationController.php
    │   ├── Requests/
    │   │   ├── CreateRoomRequest.php
    │   │   ├── SendMessageRequest.php
    │   │   ├── UpdateRoomSettingsRequest.php
    │   │   ├── UploadEmoteRequest.php
    │   │   └── CreateRankRequest.php
    │   └── Resources/
    │       ├── RoomResource.php
    │       ├── MessageResource.php
    │       ├── MemberResource.php
    │       └── NotificationResource.php
    ├── Models/
    │   ├── Room.php
    │   ├── Member.php
    │   ├── Message.php
    │   ├── Reaction.php
    │   ├── Emote.php
    │   ├── Rank.php
    │   └── Notification.php
    ├── Services/
    │   ├── RoomService.php
    │   ├── MessageService.php
    │   ├── MemberService.php
    │   ├── ReactionService.php
    │   ├── EmoteService.php
    │   ├── RankService.php
    │   └── NotificationService.php
    ├── Events/
    │   ├── MessageSent.php
    │   ├── MessageDeleted.php
    │   ├── MessageEdited.php
    │   ├── ReactionToggled.php
    │   ├── MemberJoined.php
    │   ├── MemberLeft.php
    │   ├── MemberBanned.php
    │   └── MemberRankUpgraded.php
    ├── Listeners/
    │   ├── AwardXpOnMessage.php
    │   ├── CheckRankPromotion.php
    │   ├── SendMentionNotification.php
    │   └── SendReplyNotification.php
    ├── Jobs/
    │   ├── BroadcastMessage.php
    │   └── ProcessEmoteUpload.php
    ├── Enums/
    │   ├── RoomType.php
    │   ├── MessageType.php
    │   └── MemberRole.php
    ├── Data/
    │   ├── SendMessageData.php
    │   ├── CreateRoomData.php
    │   └── RoomSettingsData.php
    ├── Contracts/
    │   ├── MessageServiceContract.php
    │   └── RankServiceContract.php
    ├── Channels/
    │   └── RoomChannel.php
    ├── database/
    │   ├── migrations/
    │   └── seeders/
    │       ├── DiscussDatabaseSeeder.php
    │       ├── DefaultEmotesSeeder.php
    │       └── DefaultRanksSeeder.php
    ├── routes/
    │   └── discuss.php
    └── tests/
        ├── Unit/
        └── Feature/
```

---

## Task List

Kerjakan task secara berurutan. Setiap task harus selesai dan tidak error sebelum lanjut ke task berikutnya. Tandai selesai dengan checklist.

---

### FASE 1 — Bootstrap & Fondasi

#### Task 1 — Setup ServiceProvider
- [ ] Buat `app/Modules/Auth/AuthServiceProvider.php`
  - `loadRoutesFrom` ke `routes/auth.php`
  - `loadMigrationsFrom` ke `database/migrations`
- [ ] Buat `app/Modules/Discuss/DiscussServiceProvider.php`
  - `loadRoutesFrom` ke `routes/discuss.php`
  - `loadMigrationsFrom` ke `database/migrations`
  - Load `Channels/RoomChannel.php`
- [ ] Daftarkan keduanya di `bootstrap/providers.php`

#### Task 2 — Enum
- [ ] `Auth/Enums/UserRole.php` → `reader | moderator | creator | admin`
- [ ] `Auth/Enums/UserStatus.php` → `active | suspended | banned`
- [ ] `Discuss/Enums/RoomType.php` → `public | private | invite_only`
- [ ] `Discuss/Enums/MessageType.php` → `text | image | sticker | system`
- [ ] `Discuss/Enums/MemberRole.php` → `member | moderator | admin`

---

### FASE 2 — Database

#### Task 3 — Migration Auth
- [ ] `create_users_table` — sesuai skema, kolom `preferences` JSON default `{}`
- [ ] `create_user_profiles_table` — kolom `preferences` JSON default `{}`
- [ ] `create_user_social_accounts_table` — index UNIQUE(provider, provider_id)
- [ ] `create_auth_sessions_table`
- [ ] `create_password_reset_tokens_table`
- [ ] Jalankan `php artisan migrate` — pastikan tidak ada error

#### Task 4 — Migration Discuss
- [ ] `create_discuss_rooms_table` — kolom `settings` JSON default `{}`
- [ ] `create_discuss_members_table`
- [ ] `create_discuss_messages_table` — kolom `attachments` JSON default `[]`, `metadata` JSON default `{}`
- [ ] `create_discuss_reactions_table` — UNIQUE(message_id, user_id, emote_id)
- [ ] `create_discuss_emotes_table`
- [ ] `create_discuss_ranks_table` — kolom `perks` JSON default `{}`
- [ ] `create_discuss_notifications_table` — kolom `payload` JSON default `{}`
- [ ] Jalankan `php artisan migrate` — pastikan tidak ada error

---

### FASE 3 — Models

#### Task 5 — Models Auth
- [ ] `Auth/Models/User.php`
  - Gunakan `HasUlids`, `HasFactory`, `Notifiable`
  - Cast: `role` → `UserRole`, `status` → `UserStatus`, `preferences` → `array`
  - `$fillable` lengkap, `$hidden` untuk `password`
  - Accessor `getAvatarUrlAttribute` — return default avatar jika null
- [ ] `Auth/Models/UserProfile.php`
  - Cast `preferences` → `array`
- [ ] `Auth/Models/UserSocialAccount.php`
- [ ] `Auth/Models/AuthSession.php`

#### Task 6 — Models Discuss
- [ ] `Discuss/Models/Room.php`
  - Cast: `type` → `RoomType`, `settings` → `array`
  - Scope: `scopeActive`, `scopePublic`
- [ ] `Discuss/Models/Member.php`
  - Cast: `role` → `MemberRole`
- [ ] `Discuss/Models/Message.php`
  - Cast: `type` → `MessageType`, `attachments` → `array`, `metadata` → `array`
  - Scope: `scopeNotDeleted`
- [ ] `Discuss/Models/Reaction.php`
- [ ] `Discuss/Models/Emote.php`
  - Scope: `scopeActive`, `scopeGlobal` (room_id null), `scopeForRoom($roomId)`
- [ ] `Discuss/Models/Rank.php`
  - Cast: `perks` → `array`
  - Scope: `scopeForRoom($roomId)`, `scopeGlobal`
- [ ] `Discuss/Models/Notification.php`
  - Cast: `payload` → `array`
  - Scope: `scopeUnread`

**Tidak boleh ada `belongsTo` atau `hasMany` yang melintasi module Auth ↔ Discuss.**

---

### FASE 4 — Factories & Seeders

#### Task 7 — Factories
- [ ] `UserFactory.php` di module Auth
- [ ] `RoomFactory.php`, `MessageFactory.php`, `MemberFactory.php` di module Discuss

#### Task 8 — Seeders
- [ ] `AuthDatabaseSeeder.php` — seed 1 user admin
- [ ] `DefaultRanksSeeder.php` — seed rank global: Newcomer (0 XP), Regular (100 XP), Veteran (500 XP), Legend (2000 XP)
- [ ] `DefaultEmotesSeeder.php` — seed minimal 5 emot global platform
- [ ] `DiscussDatabaseSeeder.php` — panggil kedua seeder di atas

---

### FASE 5 — Auth: Service & Controller

#### Task 9 — DTO Auth
- [ ] `Auth/Data/RegisterData.php` — `name`, `username`, `email`, `password`
- [ ] `Auth/Data/UserData.php` — representasi user untuk response

#### Task 10 — Auth Service
- [ ] `Auth/Contracts/AuthServiceContract.php` — definisikan interface
- [ ] `Auth/Services/AuthService.php`
  - `register(RegisterData $data): User` — buat user + profile, fire `UserRegistered`
  - `login(string $email, string $password): User` — validasi kredensial, fire `UserLoggedIn`
  - `logout(): void`
- [ ] `Auth/Services/SocialAuthService.php`
  - `handleCallback(string $provider): User` — upsert `UserSocialAccount`, buat User jika baru
- [ ] `Auth/Services/EmailVerificationService.php`
  - `send(User $user): void`
  - `verify(User $user, string $hash): bool`
- [ ] `Auth/Services/PasswordResetService.php`
  - `sendLink(string $email): void`
  - `reset(string $email, string $token, string $password): bool`

#### Task 11 — Auth Events & Listeners
- [ ] `Events/UserRegistered.php` — bawa `User $user`
- [ ] `Events/UserLoggedIn.php` — bawa `User $user`
- [ ] `Events/UserBanned.php` — bawa `User $user`
- [ ] `Listeners/SendVerificationEmail.php` — listen `UserRegistered`
- [ ] `Listeners/UpdateLastLogin.php` — listen `UserLoggedIn`, update `last_login_at`
- [ ] Daftarkan di `AuthServiceProvider` via `$listen`

#### Task 12 — Auth Form Requests
- [ ] `LoginRequest.php` — validasi `email`, `password`
- [ ] `RegisterRequest.php` — validasi `name`, `username` (unik), `email` (unik), `password` (confirmed, min 8)
- [ ] `PasswordResetRequest.php`
- [ ] `UpdateProfileRequest.php`

#### Task 13 — Auth Controllers
- [ ] `RegisterController.php` — `store()` → panggil `AuthService::register()`
- [ ] `LoginController.php` — `store()` → panggil `AuthService::login()`
- [ ] `LogoutController.php` — `destroy()`
- [ ] `EmailVerificationController.php` — `send()`, `verify()`
- [ ] `PasswordResetController.php` — `sendLink()`, `reset()`
- [ ] `SocialAuthController.php` — `redirect()`, `callback()`

#### Task 14 — Auth Routes
- [ ] `Auth/routes/auth.php`
```
POST   /register
POST   /login
POST   /logout
GET    /email/verify/{id}/{hash}
POST   /email/verification-notification
POST   /forgot-password
POST   /reset-password
GET    /auth/{provider}/redirect
GET    /auth/{provider}/callback
GET    /user              (auth)
PUT    /user/profile      (auth)
```
- [ ] Semua route yang butuh login dibungkus middleware `auth`

---

### FASE 6 — Discuss: Service & Controller

#### Task 15 — DTO Discuss
- [ ] `Discuss/Data/CreateRoomData.php`
- [ ] `Discuss/Data/SendMessageData.php`
- [ ] `Discuss/Data/RoomSettingsData.php`

#### Task 16 — Discuss Services
- [ ] `Discuss/Contracts/MessageServiceContract.php`
- [ ] `Discuss/Contracts/RankServiceContract.php`
- [ ] `Discuss/Services/RoomService.php`
  - `create(CreateRoomData $data): Room`
  - `archive(Room $room): void`
  - `generateInviteLink(Room $room): string` — simpan ke `settings.invite_link`
  - `findByContext(string $type, string $id): ?Room`
- [ ] `Discuss/Services/MemberService.php`
  - `join(string $roomId, string $userId): Member`
  - `leave(string $roomId, string $userId): void`
  - `kick(Member $member, string $byUserId): void`
  - `mute(Member $member, int $minutes): void`
  - `ban(Member $member): void`
- [ ] `Discuss/Services/MessageService.php`
  - `send(SendMessageData $data): Message` — fire `MessageSent`
  - `edit(Message $message, string $newBody): Message` — fire `MessageEdited`
  - `delete(Message $message, string $byUserId): void` — soft delete, fire `MessageDeleted`
- [ ] `Discuss/Services/ReactionService.php`
  - `toggle(string $messageId, string $userId, string $emoteId): void` — add jika belum ada, remove jika sudah ada. Fire `ReactionToggled`
- [ ] `Discuss/Services/EmoteService.php`
  - `upload(UploadedFile $file, string $code, string $name, ?string $roomId): Emote`
  - `deactivate(Emote $emote): void`
  - `availableFor(string $roomId): Collection` — global + room-specific
- [ ] `Discuss/Services/RankService.php`
  - `awardXp(Member $member, int $points): void`
  - `checkPromotion(Member $member): void` — query rank dengan `min_xp <= member.xp_points`, ambil tertinggi, update jika berubah, fire `MemberRankUpgraded`
  - `ranksForRoom(?string $roomId): Collection`
- [ ] `Discuss/Services/NotificationService.php`
  - `create(string $userId, string $type, array $payload, ?string $actorId): Notification`
  - `markAllRead(string $userId): void`
  - `unreadCount(string $userId): int`

#### Task 17 — Discuss Events & Listeners
- [ ] Semua Event di `Discuss/Events/` — implement `ShouldBroadcast`
  - `MessageSent` — broadcast ke `room.{roomId}`, bawa data message + user info (display_name, avatar_url diambil dari query User)
  - `MessageEdited` — broadcast ke `room.{roomId}`
  - `MessageDeleted` — broadcast ke `room.{roomId}`, bawa `message_id` saja
  - `ReactionToggled` — broadcast ke `room.{roomId}`
  - `MemberJoined`, `MemberLeft`, `MemberBanned` — broadcast ke `room.{roomId}`
  - `MemberRankUpgraded` — broadcast ke `private-user.{userId}`
- [ ] `Listeners/AwardXpOnMessage.php` — listen `MessageSent` → `RankService::awardXp(member, xp_per_message dari settings)`
- [ ] `Listeners/CheckRankPromotion.php` — listen `MessageSent` → `RankService::checkPromotion(member)`
- [ ] `Listeners/SendMentionNotification.php` — listen `MessageSent` → parse `metadata.mentions`, buat notif untuk tiap mention
- [ ] `Listeners/SendReplyNotification.php` — listen `MessageSent` → jika `reply_to_id` ada, buat notif untuk pemilik pesan asli
- [ ] Daftarkan semua di `DiscussServiceProvider`

#### Task 18 — Discuss Jobs
- [ ] `Jobs/ProcessEmoteUpload.php` — resize gambar emot ke 64x64, upload ke storage
- [ ] `Jobs/BroadcastMessage.php` — untuk broadcast pesan ke room besar (queue-based)

#### Task 19 — Broadcast Channel Auth
- [ ] `Discuss/Channels/RoomChannel.php`
  - Channel `room.{roomId}` — authorize jika user adalah member aktif dan tidak di-ban
  - Channel `private-user.{userId}` — authorize jika `$user->id === $userId`
- [ ] Daftarkan di `routes/channels.php`

#### Task 20 — Discuss Form Requests
- [ ] `CreateRoomRequest.php`
- [ ] `SendMessageRequest.php` — validasi `body` atau `attachments` wajib ada salah satu
- [ ] `UpdateRoomSettingsRequest.php`
- [ ] `UploadEmoteRequest.php` — validasi file image max 512KB, format png/gif/webp
- [ ] `CreateRankRequest.php`

#### Task 21 — Discuss API Resources
- [ ] `RoomResource.php` — id, name, slug, type, settings, member_count
- [ ] `MessageResource.php` — id, body, type, user info (query dari Auth\Models\User), reactions grouped by emote, reply_to, metadata, timestamps
- [ ] `MemberResource.php` — user info, role, rank info, xp_points
- [ ] `NotificationResource.php` — id, type, actor info, payload, is_read, created_at

#### Task 22 — Discuss Controllers
- [ ] `RoomController.php` — `index`, `store`, `show`, `update`, `destroy`
- [ ] `MemberController.php` — `store` (join), `destroy` (leave), `kick`, `mute`, `ban`
- [ ] `MessageController.php` — `index` (paginated 50/load), `store`, `update`, `destroy`
- [ ] `ReactionController.php` — `store` (toggle)
- [ ] `EmoteController.php` — `index`, `store`, `destroy`
- [ ] `RankController.php` — `index`, `store`, `update`, `destroy`
- [ ] `NotificationController.php` — `index`, `update` (mark read), `markAllRead`

#### Task 23 — Discuss Routes
- [ ] `Discuss/routes/discuss.php` — semua dibungkus `auth` middleware
```
GET    /rooms
POST   /rooms
GET    /rooms/{slug}
PUT    /rooms/{slug}
DELETE /rooms/{slug}

POST   /rooms/{slug}/join
DELETE /rooms/{slug}/leave
POST   /rooms/{slug}/members/{memberId}/kick
POST   /rooms/{slug}/members/{memberId}/mute
POST   /rooms/{slug}/members/{memberId}/ban

GET    /rooms/{slug}/messages
POST   /rooms/{slug}/messages
PUT    /rooms/{slug}/messages/{messageId}
DELETE /rooms/{slug}/messages/{messageId}

POST   /rooms/{slug}/messages/{messageId}/reactions

GET    /emotes
POST   /emotes
DELETE /emotes/{emoteId}

GET    /rooms/{slug}/ranks
POST   /rooms/{slug}/ranks
PUT    /rooms/{slug}/ranks/{rankId}
DELETE /rooms/{slug}/ranks/{rankId}

GET    /notifications
POST   /notifications/read-all
PUT    /notifications/{id}
```

---

### FASE 7 — Testing

#### Task 24 — Unit Tests Auth
- [ ] `AuthServiceTest.php` — test register, login, invalid login
- [ ] `UserModelTest.php` — test cast, accessor avatar default

#### Task 25 — Unit Tests Discuss
- [ ] `RankServiceTest.php` — test awardXp, checkPromotion naik dan tidak naik
- [ ] `MessageServiceTest.php` — test send, edit, delete (soft)

#### Task 26 — Feature Tests Auth
- [ ] `RegisterTest.php` — sukses, email duplikat, validasi gagal
- [ ] `LoginTest.php` — sukses, password salah, user banned
- [ ] `SocialAuthTest.php` — mock Socialite

#### Task 27 — Feature Tests Discuss
- [ ] `JoinRoomTest.php` — join public, join private (ditolak), join banned (ditolak)
- [ ] `SendMessageTest.php` — kirim pesan, validasi kosong, user dimute
- [ ] `ReactionTest.php` — toggle add, toggle remove

---

## Catatan Implementasi

**Cara ambil data user di Discuss (cross-module query):**
```php
// ✅ Benar — query langsung dengan select terbatas
use App\Modules\Auth\Models\User;

$user = User::select('id', 'display_name', 'avatar_url')
            ->where('id', $userId)
            ->first();

// ❌ Salah — jangan definisikan relationship di Model
// class Message extends Model {
//     public function user() { return $this->belongsTo(User::class); } // DILARANG
// }
```

**Cara tambah fitur baru tanpa migration (contoh: tambah slow mode):**
```php
// Di RoomService — tidak perlu kolom baru, cukup update key di settings JSON
$room->update([
    'settings' => array_merge($room->settings, [
        'slow_mode_seconds' => 30
    ])
]);
```

**XP per pesan diambil dari settings room:**
```php
// Di AwardXpOnMessage listener
$xpPerMessage = $event->room->settings['xp_per_message'] ?? 5;
$this->rankService->awardXp($member, $xpPerMessage);
```

