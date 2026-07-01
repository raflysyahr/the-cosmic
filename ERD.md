# ERD — Auth & Discuss Module
> Monolith Modular · Laravel + Inertia React  
> Tanpa FK constraint antar module · Extensible via JSON columns

---

## Prinsip Desain

| Prinsip | Implementasi |
|---|---|
| **No cross-module FK** | Relasi antar module hanya lewat `user_id` sebagai plain ULID, di-resolve di service layer |
| **Extensible tanpa migration** | Kolom `JSON` (`settings`, `metadata`, `preferences`, `perks`, `payload`) untuk fitur baru |
| **Polymorphic logis** | `context_type` + `context_id` untuk kaitkan room ke komik/chapter/apapun |
| **Prefix tabel per module** | `auth_*` dan `discuss_*` untuk isolasi yang jelas |
| **ULID sebagai PK** | Non-sequential, aman untuk distributed, mudah di-debug |

---

## Strategi Extensible Tanpa Migration

Setiap tabel yang berpotensi berkembang memiliki kolom JSON khusus:

```
users.preferences         → notif settings, theme, language, dll
user_profiles.preferences → extended profile settings
discuss_rooms.settings    → slow_mode, max_members, welcome_message, dll
discuss_messages.metadata → mentions[], link_preview, sticker_id, poll_id, dll
discuss_ranks.perks       → exclusive_emotes[], color_name, custom_badge, dll
discuss_notifications.payload → room_id, message_id, rank_name, dll (per type)
```

**Cara tambah fitur baru tanpa migration:**
1. Tulis key baru ke kolom JSON via Laravel mutator atau `array_merge`
2. Tambah validasi di FormRequest / DTO
3. Tidak perlu `php artisan migrate`

---

## Module Auth

> `app/Modules/Auth`

### `users`
*Identitas utama seluruh platform*

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` 🔑 | `ULID` | Primary key |
| `username` ⚿ | `VARCHAR(50)` | Unik, untuk @mention |
| `email` ⚿ | `VARCHAR(191)` | Login identifier, unik |
| `password` | `VARCHAR(255)` | Bcrypt hashed, nullable (OAuth user) |
| `display_name` | `VARCHAR(100)` | Nama tampil di discuss |
| `avatar_url` | `TEXT` | URL avatar, nullable |
| `role` | `ENUM` | `reader` \| `moderator` \| `creator` \| `admin` |
| `status` | `ENUM` | `active` \| `suspended` \| `banned` |
| `email_verified_at` | `TIMESTAMP` | Null = belum verifikasi |
| `last_login_at` | `TIMESTAMP` | Tracking aktivitas |
| `preferences` | `JSON` | Notif settings, theme, language, dll *(extensible)* |
| `created_at` | `TIMESTAMP` | |
| `updated_at` | `TIMESTAMP` | |

**Index:** `UNIQUE(username)`, `UNIQUE(email)`

**Contoh `preferences` JSON:**
```json
{
  "theme": "dark",
  "language": "id",
  "notifications": {
    "mention": true,
    "reply": true,
    "rank_up": true
  },
  "email_digest": "weekly"
}
```

---

### `user_profiles`
*Data extended profil, dipisah agar tabel `users` tetap ringan*

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` 🔑 | `ULID` | Primary key |
| `user_id` ⟶ | `ULID` | → `users.id` *(logis, no FK)* |
| `bio` | `TEXT` | Deskripsi profil, nullable |
| `website_url` | `VARCHAR(255)` | Nullable |
| `location` | `VARCHAR(100)` | Nullable |
| `preferences` | `JSON` | Data profil extended *(extensible)* |
| `updated_at` | `TIMESTAMP` | |

**Index:** `UNIQUE(user_id)`

---

### `user_social_accounts`
*OAuth provider (Google, dll via Laravel Socialite)*

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` 🔑 | `ULID` | Primary key |
| `user_id` ⟶ | `ULID` | → `users.id` *(logis)* |
| `provider` | `VARCHAR(30)` | `google` \| `github` \| `discord` |
| `provider_id` ⚿ | `VARCHAR(100)` | ID dari provider |
| `access_token` | `TEXT` | Encrypted |
| `refresh_token` | `TEXT` | Nullable |
| `token_expires_at` | `TIMESTAMP` | Nullable |
| `created_at` | `TIMESTAMP` | |

**Index:** `UNIQUE(provider, provider_id)`

---

### `auth_sessions`
*Session management (menggantikan tabel `sessions` default Laravel)*

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` 🔑 | `VARCHAR(100)` | Session token (hashed) |
| `user_id` ⟶ | `ULID` | → `users.id` *(logis, nullable = guest)* |
| `ip_address` | `VARCHAR(45)` | IPv4 / IPv6 |
| `user_agent` | `TEXT` | Browser / device info |
| `payload` | `LONGTEXT` | Serialized session data |
| `last_activity` | `INT` | Unix timestamp |

**Index:** `INDEX(user_id)`, `INDEX(last_activity)`

---

### `password_reset_tokens`
*Token reset password & verifikasi email (standar Laravel)*

| Kolom | Tipe | Keterangan |
|---|---|---|
| `email` 🔑 | `VARCHAR(191)` | PK sekaligus identifier |
| `token` | `VARCHAR(255)` | Hashed token |
| `created_at` | `TIMESTAMP` | Expire dicek manual (60 menit) |

---

## Module Discuss

> `app/Modules/Discuss`

### `discuss_rooms`
*Group / channel utama discuss*

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` 🔑 | `ULID` | Primary key |
| `slug` ⚿ | `VARCHAR(100)` | URL-friendly, unik |
| `name` | `VARCHAR(100)` | Nama room |
| `description` | `TEXT` | Deskripsi room, nullable |
| `cover_url` | `TEXT` | Gambar cover room, nullable |
| `type` | `ENUM` | `public` \| `private` \| `invite_only` |
| `owner_user_id` ⟶ | `ULID` | → `users.id` *(logis)* |
| `context_type` | `VARCHAR(50)` | `comic` \| `chapter` \| `general` *(polymorphic)* |
| `context_id` | `ULID` | ID komik/chapter, nullable *(polymorphic)* |
| `is_active` | `BOOLEAN` | Aktif / diarsipkan |
| `settings` | `JSON` | Konfigurasi room *(extensible)* |
| `created_at` | `TIMESTAMP` | |
| `updated_at` | `TIMESTAMP` | |

**Index:** `UNIQUE(slug)`, `INDEX(context_type, context_id)`, `INDEX(owner_user_id)`

**Contoh `settings` JSON:**
```json
{
  "slow_mode_seconds": 10,
  "max_members": 500,
  "welcome_message": "Selamat datang di room komik X!",
  "allow_images": true,
  "allow_stickers": true,
  "xp_per_message": 5,
  "invite_link": "abc123",
  "invite_expires_at": null
}
```

---

### `discuss_members`
*Keanggotaan user di sebuah room*

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` 🔑 | `ULID` | Primary key |
| `room_id` ⟶ | `ULID` | → `discuss_rooms.id` *(logis)* |
| `user_id` ⟶ | `ULID` | → `users.id` *(logis)* |
| `role` | `ENUM` | `member` \| `moderator` \| `admin` |
| `rank_id` ⟶ | `ULID` | → `discuss_ranks.id` *(logis, nullable)* |
| `xp_points` | `INT` | Poin aktivitas di room ini, default 0 |
| `muted_until` | `TIMESTAMP` | Null = tidak dimute |
| `is_banned` | `BOOLEAN` | Ban dari room ini |
| `last_read_at` | `TIMESTAMP` | Untuk hitung unread message badge |
| `joined_at` | `TIMESTAMP` | |

**Index:** `UNIQUE(room_id, user_id)`, `INDEX(user_id)`, `INDEX(rank_id)`

---

### `discuss_messages`
*Isi pesan dalam room*

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` 🔑 | `ULID` | Primary key |
| `room_id` ⟶ | `ULID` | → `discuss_rooms.id` *(logis)* |
| `user_id` ⟶ | `ULID` | → `users.id` *(logis)* |
| `reply_to_id` ⟶ | `ULID` | → `discuss_messages.id` self-ref *(logis, nullable)* |
| `type` | `ENUM` | `text` \| `image` \| `sticker` \| `system` |
| `body` | `TEXT` | Isi pesan, nullable (misal: pesan hanya gambar) |
| `attachments` | `JSON` | Array URL file/gambar *(extensible)* |
| `is_edited` | `BOOLEAN` | Pernah diedit, default false |
| `is_deleted` | `BOOLEAN` | Soft delete, pesan tampil sebagai *"Pesan dihapus"* |
| `deleted_by_user_id` ⟶ | `ULID` | Siapa yang hapus (mod/owner), nullable |
| `metadata` | `JSON` | Data extended pesan *(extensible)* |
| `created_at` | `TIMESTAMP` | |
| `updated_at` | `TIMESTAMP` | |

**Index:** `INDEX(room_id, created_at)`, `INDEX(user_id)`, `INDEX(reply_to_id)`

**Contoh `metadata` JSON:**
```json
{
  "mentions": ["user_id_1", "user_id_2"],
  "link_preview": {
    "url": "https://example.com",
    "title": "Judul halaman",
    "thumbnail": "https://..."
  },
  "poll_id": null,
  "forwarded_from_id": null,
  "sticker_pack_id": null
}
```

> **Cara tambah fitur poll, forward, link preview:** cukup tambah key baru di `metadata` — tidak perlu migration.

---

### `discuss_reactions`
*Reaksi / emot per pesan*

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` 🔑 | `ULID` | Primary key |
| `message_id` ⟶ | `ULID` | → `discuss_messages.id` *(logis)* |
| `user_id` ⟶ | `ULID` | → `users.id` *(logis)* |
| `emote_id` ⟶ | `ULID` | → `discuss_emotes.id` *(logis)* |
| `created_at` | `TIMESTAMP` | |

**Index:** `UNIQUE(message_id, user_id, emote_id)`, `INDEX(message_id)`

---

### `discuss_emotes`
*Custom emot per room atau global platform*

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` 🔑 | `ULID` | Primary key |
| `room_id` ⟶ | `ULID` | → `discuss_rooms.id` *(logis)*, null = emot global |
| `code` ⚿ | `VARCHAR(50)` | `:nama_emot:` untuk dipanggil saat chat |
| `name` | `VARCHAR(100)` | Nama display emot |
| `image_url` | `TEXT` | URL gambar emot (Cloudflare R2 / S3) |
| `is_animated` | `BOOLEAN` | GIF support |
| `is_active` | `BOOLEAN` | On/off tanpa hapus data |
| `uploaded_by_user_id` ⟶ | `ULID` | → `users.id` *(logis)* |
| `created_at` | `TIMESTAMP` | |

**Index:** `UNIQUE(code)`, `INDEX(room_id)`

---

### `discuss_ranks`
*Sistem pangkat berdasarkan XP aktivitas*

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` 🔑 | `ULID` | Primary key |
| `room_id` ⟶ | `ULID` | → `discuss_rooms.id` *(logis)*, null = rank global |
| `name` | `VARCHAR(50)` | Newcomer, Regular, Veteran, Legend, dll |
| `label_color` | `VARCHAR(7)` | Hex color badge (misal `#6c8ef5`) |
| `icon_url` | `TEXT` | Ikon pangkat, nullable |
| `min_xp` | `INT` | Minimum XP untuk naik ke rank ini |
| `order` | `INT` | Urutan tampil (1 = terendah) |
| `perks` | `JSON` | Hak akses berdasarkan rank *(extensible)* |
| `created_at` | `TIMESTAMP` | |

**Index:** `INDEX(room_id, order)`

**Contoh `perks` JSON:**
```json
{
  "exclusive_emotes": ["emote_id_1", "emote_id_2"],
  "custom_name_color": "#f5c96c",
  "badge_url": "https://...",
  "can_attach_images": true,
  "slow_mode_exempt": false,
  "max_reactions_per_message": 5
}
```

> **Cara tambah benefit rank baru:** cukup tambah key baru di `perks` — tidak perlu migration.

---

### `discuss_notifications`
*Notifikasi in-app untuk semua tipe event discuss*

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` 🔑 | `ULID` | Primary key |
| `user_id` ⟶ | `ULID` | → `users.id` penerima *(logis)* |
| `type` | `VARCHAR(50)` | `mention` \| `reply` \| `rank_up` \| `room_invite` \| ... |
| `actor_user_id` ⟶ | `ULID` | → `users.id` pengirim *(logis, nullable = system)* |
| `payload` | `JSON` | Data konteks per tipe notif *(extensible)* |
| `is_read` | `BOOLEAN` | Default false |
| `created_at` | `TIMESTAMP` | |

**Index:** `INDEX(user_id, is_read)`, `INDEX(user_id, created_at)`

**Contoh `payload` JSON per tipe:**
```json
// type: mention
{ "room_id": "...", "message_id": "...", "room_name": "Komik X" }

// type: reply
{ "room_id": "...", "message_id": "...", "parent_message_id": "..." }

// type: rank_up
{ "room_id": "...", "rank_id": "...", "rank_name": "Veteran", "rank_color": "#f5c96c" }

// type: room_invite
{ "room_id": "...", "room_name": "Komik Y", "invite_by_user_id": "..." }
```

> **Cara tambah tipe notif baru:** cukup tambah `type` string baru dan definisikan struktur `payload`-nya — tidak perlu migration.

---

## Relasi Logis Antar Module

Semua referensi berikut adalah **logis** (tidak ada FK constraint di database):

```
users.id
  ├── user_profiles.user_id
  ├── user_social_accounts.user_id
  ├── auth_sessions.user_id
  ├── discuss_rooms.owner_user_id
  ├── discuss_members.user_id
  ├── discuss_messages.user_id
  ├── discuss_messages.deleted_by_user_id
  ├── discuss_reactions.user_id
  ├── discuss_emotes.uploaded_by_user_id
  ├── discuss_notifications.user_id
  └── discuss_notifications.actor_user_id

discuss_rooms.id
  ├── discuss_members.room_id
  ├── discuss_messages.room_id
  ├── discuss_emotes.room_id
  └── discuss_ranks.room_id

discuss_messages.id
  ├── discuss_messages.reply_to_id  (self-referential)
  └── discuss_reactions.message_id

discuss_emotes.id
  └── discuss_reactions.emote_id

discuss_ranks.id
  └── discuss_members.rank_id
```

---

## Ringkasan Tabel

| Module | Tabel | Fungsi |
|---|---|---|
| Auth | `users` | Identitas utama |
| Auth | `user_profiles` | Data extended profil |
| Auth | `user_social_accounts` | OAuth provider |
| Auth | `auth_sessions` | Session management |
| Auth | `password_reset_tokens` | Reset & verifikasi |
| Discuss | `discuss_rooms` | Group / channel |
| Discuss | `discuss_members` | Keanggotaan + XP + rank |
| Discuss | `discuss_messages` | Isi chat |
| Discuss | `discuss_reactions` | Reaksi emot per pesan |
| Discuss | `discuss_emotes` | Custom emot |
| Discuss | `discuss_ranks` | Sistem pangkat |
| Discuss | `discuss_notifications` | Notifikasi in-app |

**Total: 12 tabel · 2 module · 0 cross-module FK constraint**

---

## Keterangan Simbol

| Simbol | Arti |
|---|---|
| 🔑 | Primary Key |
| ⚿ | Unique Key |
| ⟶ | Referensi logis (no FK constraint) |

