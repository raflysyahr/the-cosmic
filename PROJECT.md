# PROJECT.md — The Cosmic

> Dokumen ini merangkum **apa saja fitur yang benar-benar ada** di codebase saat ini (diverifikasi langsung dari kode, bukan dari rencana/dokumentasi lama), stack teknis, dan status known-issues. Untuk aturan arsitektur & cara kerja tim, baca `AGENTS.md`.

Terakhir diverifikasi: Juli 2026, dengan cara membaca langsung struktur `app/Modules`, `app/Http`, `app/Services`, `resources/js/Pages`, dan menjalankan test suite.

---

## 1. Ringkasan Project

The Cosmic adalah web comic reader (mirip Komikcast) yang dibangun sebagai **modular monolith**: Laravel 13 + Inertia.js + React/TypeScript di satu repo yang sama, tanpa API terpisah untuk frontend-nya sendiri. Dua modul custom (`Auth`, `Discuss`) dibangun mengikuti pola modular yang ketat; sisanya (baca komik) adalah kode "legacy-style" yang lebih longgar, mengonsumsi data dari layanan pihak ketiga.

---

## 2. Stack Teknis

| Layer | Teknologi | Versi |
|---|---|---|
| Backend framework | Laravel | ^13.8 |
| PHP | — | ^8.3 |
| Frontend bridge | Inertia.js (React adapter) | ^2.0 |
| Frontend | React | ^18.2 |
| Bahasa frontend | TypeScript | ^6.0.3 (campur `.tsx`/`.jsx`) |
| Build tool | Vite | ^6.4 |
| Styling | Tailwind CSS | ^3.2 (`@tailwindcss/forms`) |
| Realtime | Laravel Reverb (WebSocket server) + Laravel Echo + Pusher protocol client | Reverb ^1.10, laravel-echo ^2.3, pusher-js ^8.5 |
| Auth token (API) | Laravel Sanctum | ^4.0 |
| OAuth | Laravel Socialite | ^5.28 |
| Ikon | lucide-react | — |
| Emoji rendering | react-fluentui-emoji | — |
| Testing | PHPUnit | ^12.5 |
| Linting/format PHP | Laravel Pint | ^1.27 |
| DB (test) | SQLite (in-memory, via `RefreshDatabase`) | — |
| DB (produksi, diasumsikan) | MySQL (skema pakai `ENUM`, `ULID`) | — |

Primary key semua tabel custom (`Auth`, `Discuss`) menggunakan **ULID**, bukan auto-increment integer.

---

## 3. Peta Modul & Kode

```
app/
├── Modules/
│   ├── Auth/       ← modular, mengikuti AGENTS.md ketat
│   └── Discuss/    ← modular, mengikuti AGENTS.md ketat
├── Http/Controllers/Api/V1/   ← legacy, thin proxy ke layanan komik eksternal
├── Services/KomikcastService.php  ← proxy tunggal ke worker eksternal
└── Models/         ← KOSONG (tidak ada model legacy aktif)
```

Modul `Auth` dan `Discuss` masing-masing punya `ServiceProvider`, `routes/`, `Database/Migrations`, `tests/` sendiri — didaftarkan di `bootstrap/providers.php`.

---

## 4. Fitur — Auth Module

Semua terverifikasi ada di `app/Modules/Auth/`.

| Fitur | Status | Keterangan |
|---|---|---|
| Register (email/password) | ✅ Ada | `RegisterController` → `AuthService::register()`, fire `UserRegistered` |
| Login (email/password) | ✅ Ada | `LoginController` → `AuthService::login()`, cek status `banned`/`suspended` |
| Logout | ✅ Ada | `LogoutController`; **ada duplikasi** — lihat §7 |
| Verifikasi email | ✅ Ada | `EmailVerificationController` + `VerifyEmailNotification` |
| Reset password | ✅ Ada | `PasswordResetController` + `PasswordResetNotification` |
| OAuth (Socialite, provider generic) | ✅ Ada (kode) | `SocialAuthController` — stateless, cocok untuk konsumsi token API. **Kredensial provider (Google/GitHub dll.) tidak dikonfigurasi di `config/services.php`** — perlu ditambahkan di `.env` + config sebelum benar-benar bisa dipakai. |
| Profil sendiri (edit) | ✅ Ada, **1 bug aktif** | `ProfileController::edit()` — lihat §7 (bug `App\Models\Bookmark`) |
| Profil publik (lihat user lain) | ✅ Ada | `GET /u/{username}` → `ProfileController::showPublic()`. Sengaja **tidak** expose email & jumlah bookmark (privat). Menampilkan stat: messages, rooms, xp. Tombol "Message" langsung buka DM. |
| Role user | ✅ Ada | Enum `UserRole`: `reader \| moderator \| creator \| admin` |
| Status user | ✅ Ada | Enum `UserStatus`: `active \| suspended \| banned` |

---

## 5. Fitur — Discuss Module (Group Chat + DM)

Semua terverifikasi ada di `app/Modules/Discuss/`. Real-time via Laravel Reverb (WebSocket), broadcasting pakai presence channel (`Echo.join`).

### 5.1 Room / Group Chat
| Fitur | Status | Keterangan |
|---|---|---|
| Tipe room | ✅ Ada | 3 tipe: `public`, `private`, `invite_only` (enum `RoomType`) |
| Buat room | ✅ Ada | `RoomController::store()` |
| List room publik + room milik user | ✅ Ada | `RoomService::roomsForUser()` |
| Join room publik | ✅ Ada (baru diperbaiki) | Tombol "Join Room" di UI `Room.tsx`; hanya `public` yang bisa di-join langsung, `private`/`invite_only` ditolak |
| Room otomatis per komik (`comic-{slug}`) | ✅ Ada | `RoomService::findOrCreateForComic()`, dipicu saat user buka `/discuss/comic-{slug}` |
| Arsip room | ✅ Ada | `RoomService::archive()` (soft: `is_active=false`) |
| Invite link | ⚠️ Sebagian | `RoomService::generateInviteLink()` ada (generate token acak), tapi **belum ada endpoint/flow untuk memvalidasi link tsb saat join** — room `invite_only` saat ini tidak bisa di-join oleh siapa pun lewat jalur normal |

### 5.2 Direct Message (1:1 chat)
| Fitur | Status | Keterangan |
|---|---|---|
| Mulai/buka DM dengan user lain | ✅ Ada | `POST /direct/{username}` → `DirectController::open()`, reuse `Room` dengan `context_type=direct`, `context_id` deterministik dari 2 user id |
| List semua DM | ✅ Ada | `GET /direct` → `Discuss/DirectList.tsx` |
| Tab navigasi Chats/Groups | ✅ Ada | Di `Discuss/Index.tsx` dan `Discuss/DirectList.tsx` |
| Halaman chat DM (reuse `Room.tsx`) | ✅ Ada | Header otomatis tampilkan nama & avatar lawan bicara, bukan nama room; member sidebar & pinned message disembunyikan untuk DM |
| Proteksi privasi DM | ✅ Ada | Non-member (termasuk yang bukan bagian dari DM tsb) mendapat 403 saat akses `/discuss/{slug}` |

### 5.3 Pesan
| Fitur | Status | Keterangan |
|---|---|---|
| Kirim pesan | ✅ Ada | Validasi member aktif & tidak sedang di-mute |
| Edit pesan | ✅ Ada | **Hanya pemilik pesan** (diperbaiki dari bug bypass sebelumnya) |
| Hapus pesan (soft delete) | ✅ Ada | Pemilik pesan **atau** moderator/admin room |
| Reply ke pesan | ✅ Ada | `reply_to_id` |
| Reaksi/emote di pesan | ✅ Ada | `ReactionService::toggle()` — toggle add/remove |
| Real-time push pesan baru/edit/delete | ✅ Ada | Broadcast via `PresenceChannel`, event `MessageSent`/`MessageEdited`/`MessageDeleted` |
| Tipe pesan | ✅ Ada | Enum `MessageType`: `text \| image \| sticker \| system` |
| Custom emote (upload) | ✅ Ada | `EmoteService::upload()`, per-room atau global |
| Slow mode, XP per pesan | ✅ Ada (via JSON `settings`) | Disimpan di kolom `settings` room, bukan kolom terpisah |

### 5.4 Moderasi Member
| Fitur | Status | Keterangan |
|---|---|---|
| Kick member | ✅ Ada, **sudah ada popup sukses/gagal** | Hanya moderator/admin; tidak bisa kick diri sendiri; moderator tidak bisa kick sesama moderator/admin |
| Mute member (durasi custom) | ✅ Ada, **sudah ada popup input durasi** | Perbaikan bug: dulu tidak mengirim `minutes`, sekarang via popup input |
| Ban member | ✅ Ada, **sudah ada popup sukses/gagal** | Permanen, dengan konfirmasi dulu |
| Leave room | ✅ Ada | `MemberService::leave()` |
| List member per room | ✅ Ada | Diurutkan admin → moderator → member (sort di PHP, bukan SQL `FIELD()`, supaya portable) |

### 5.5 Sistem Rank/XP
| Fitur | Status | Keterangan |
|---|---|---|
| XP otomatis per pesan | ✅ Ada | `AwardXpOnMessage` listener, jumlah XP dari `room.settings.xp_per_message` |
| Auto-promosi rank | ✅ Ada | `CheckRankPromotion` listener, fire `MemberRankUpgraded` |
| Rank per-room & global | ✅ Ada | `RankService::ranksForRoom()`, seeded lewat `DefaultRanksSeeder` (Newcomer/Regular/Veteran/Legend) |
| CRUD rank (admin room) | ✅ Ada | `RankController` |

### 5.6 Notifikasi
| Fitur | Status | Keterangan |
|---|---|---|
| Notifikasi mention | ✅ Ada | `SendMentionNotification` listener, parse `metadata.mentions` |
| Notifikasi reply | ✅ Ada | `SendReplyNotification` listener |
| Mark read / mark all read | ✅ Ada | `NotificationController` |

---

## 6. Fitur — Baca Komik (Non-Modular / Legacy)

Modul ini **tidak** mengikuti pola `Service+Contract+DTO` ketat seperti `Auth`/`Discuss` — murni thin-proxy ke layanan eksternal.

| Fitur | Status | Keterangan |
|---|---|---|
| List/browse series | ✅ Ada | `KomikcastService::getSeries()` — proxy ke worker eksternal (`nice-try-your-job.xor96982.workers.dev`) |
| Cari series | ✅ Ada | `KomikcastService::search()` |
| Trending | ✅ Ada | `KomikcastService::getTrending()` |
| Detail series | ✅ Ada | `KomikcastService::getDetail()` |
| List genre & filter by genre | ✅ Ada | `KomikcastService::getGenres()` |
| List chapter + baca chapter (gambar) | ✅ Ada | `getChapters()`, `getChapterPages()` |
| Proxy gambar (hindari hotlink/CORS) | ✅ Ada | `KomikcastService::proxyImage()` → `/api/v1/image` |
| Bookmark | ✅ Ada — **tapi 100% client-side** | `useBookmarks()` hook, simpan di `localStorage`, **tidak pernah menyentuh backend/database** |
| Riwayat baca | ✅ Ada — **tapi 100% client-side** | `useReadingHistory()` hook, `localStorage` |

⚠️ Semua data komik (judul, cover, chapter, gambar) berasal dari **layanan pihak ketiga eksternal**, bukan database lokal. Availability fitur baca bergantung penuh pada uptime worker tsb.

---

## 7. Known Issues — Terverifikasi, Belum Diperbaiki

Bagian ini murni katalog bug yang ditemukan selama audit & pengembangan berjalan, agar tidak hilang dari catatan.

| # | Bug | Lokasi | Dampak |
|---|---|---|---|
| 1 | `ProfileController::edit()` memakai `App\Models\Bookmark` yang **tidak ada** (bookmark sebenarnya client-side, lihat §6) | `app/Modules/Auth/Http/Controllers/ProfileController.php` | Halaman `/profile` (profil sendiri) **fatal error** setiap diakses |
| 2 | Dua sistem auth paralel & terputus | `app/Http/Controllers/Api/V1/AuthController.php` (proxy `KomikcastService`) vs `App\Modules\Auth` (auth asli) | `/api/v1/auth/login` & `/register` tidak membuat sesi/lokal user — membingungkan, berpotensi disalahgunakan jika terekspos ke publik tanpa disadari |
| 3 | Invite-only room tidak punya jalur join yang valid | `RoomService::generateInviteLink()` ada, tapi tidak ada validasi token saat join | Room tipe `invite_only` saat ini **tidak bisa dimasuki siapa pun** lewat alur normal |
| 4 | Duplikasi endpoint logout | `GET /logout` (di `web.php`, closure) vs `POST /api/logout` (`LogoutController`) | Tidak dipakai konsisten dari satu tempat; salah satunya kemungkinan dead code — perlu diverifikasi endpoint mana yang benar-benar dipanggil frontend |
| 5 | `bootstrap/app.php` mereferensikan `routes/channels.php` yang tidak ada | `bootstrap/app.php` | Tidak fatal (Laravel skip diam-diam), tapi channel auth sebenarnya di-`require` manual dari `DiscussServiceProvider::boot()` — berpotensi membingungkan kontributor baru |
| 6 | `RankController`, `EmoteController`, dsb. belum ada feature test | `app/Modules/Discuss/Http/Controllers/{Rank,Emote}Controller.php` | Belum ada jaminan test untuk endpoint ini, walau `AGENTS.md` mewajibkan setiap endpoint HTTP punya feature test |
| 7 | `EmotePicker.tsx` dan `RankBadge.tsx` — dead code | `resources/js/Components/discuss/` | Tidak diimpor di mana pun; kemungkinan draft komponen yang tidak jadi dipakai (ada `EmojiPicker.tsx` terpisah yang aktif dipakai) |
| 8 | **Dua design system berbeda dipakai bersamaan** | `AGENTS_UI.md` mendefinisikan tema monokrom hitam-putih dengan hex hardcoded (`#111`, `#555`, `#2A2A2A`, tanpa `rounded-xl`/shadow) — dipakai konsisten di `Profile.tsx`, `Bookmarks.tsx`, `Home.tsx`, dll. Tapi modul `Discuss` (`Room.tsx`, `Index.tsx`, `DirectList.tsx`, `DiscussRoomCard.tsx`) memakai **design-token Tailwind** (`bg-surface`, `text-primary`, `text-on-surface-variant`, dst.) yang tidak terdaftar sama sekali di `AGENTS_UI.md`. | Halaman `Discuss/*` akan terlihat visual berbeda dari halaman lain (komik, profil). Perlu keputusan: pertahankan dua sistem (kalau memang disengaja sebagai tema berbeda per area), atau satukan salah satu jadi standar tunggal. Belum diverifikasi mana yang dimaksud sebagai sumber kebenaran saat ini. |

**Sudah diperbaiki (dicatat sebagai referensi histori):** bypass otorisasi edit pesan, bypass otorisasi kick/mute/ban, salah resolve `Member` di endpoint moderasi (pakai `user_id` bukan `Member.id`), `RoomController`/`RoomResource` crash karena relasi Eloquent yang tidak ada, `MemberService::listForRoom()` pakai fungsi SQL non-portable (`FIELD()`), route `GET /rooms/{slug}/members` yang belum terdaftar, tidak ada membership check saat akses room privat/DM.

---

## 8. Dokumen Pendukung di Root Project

Selain `AGENTS.md` dan `PROJECT.md`, ada beberapa file markdown lama di root yang berisi detail lebih dalam untuk topik spesifik. Isinya sebagian besar sudah terangkum di sini, tapi dipertahankan sebagai referensi detail:

| File | Isi | Status |
|---|---|---|
| `ERD.md` | Skema database lengkap per kolom (tipe, index, contoh JSON) untuk modul Auth & Discuss | Referensi detail schema — lebih lengkap dari ringkasan di dokumen ini |
| `MODULE_STRUCTURE.md` | Struktur folder lengkap per file untuk modul Auth & Discuss | Duplikasi sebagian besar dari §3 di atas, dengan detail file lebih rinci |
| `AGENTS_UI.md` | **Design system UI** (warna, tipografi, spacing, komponen wajib) untuk halaman non-Discuss | Lihat bug #8 — desain di sini **berbeda** dari yang dipakai di modul Discuss |
| `0f41.md`, `hh.md`, `session-ses_0f41.md`, `auth.md` | Kemungkinan transkrip/catatan sesi AI lama | Belum diverifikasi isinya; kandidat cleanup, belum dihapus karena belum ada konfirmasi eksplisit |

---

## 9. Menjalankan Project

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build      # atau: npm run dev untuk development
php artisan test    # jalankan seluruh test suite
```

Untuk development real-time (chat), jalankan juga:
```bash
php artisan reverb:start
php artisan queue:listen
```

`composer run dev` menjalankan server, queue listener, log viewer (`pail`), dan Vite dev server sekaligus secara paralel.
