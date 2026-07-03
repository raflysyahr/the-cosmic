# Implementasi Private Chat / Direct Message

## Overview
Fitur ini memungkinkan user untuk melakukan chat pribadi (1:1) antar user menggunakan sistem `discuss_rooms` yang sudah ada.

**Strategi Utama:**
- Gunakan `context_type = 'direct'`
- `context_id` berformat `smaller_id_larger_id` (untuk uniqueness)
- Buat `DirectController` baru (tidak extend MemberController)

---

## 1. Backend Implementation

### A. Update RoomService.php
Lokasi: `app/Modules/Discuss/Services/RoomService.php`

Tambahkan method berikut:

```php
/**
 * Generate unique context_id untuk direct chat
 */
private function generateDirectContextId(string $user1Id, string $user2Id): string
{
    $ids = [$user1Id, $user2Id];
    sort($ids);
    return implode('_', $ids);
}

/**
 * Find or Create Direct Chat Room
 */
public function findOrCreateDirectChat(string $user1Id, string $user2Id): Room
{
    $contextId = $this->generateDirectContextId($user1Id, $user2Id);

    $room = Room::where('context_type', 'direct')
                ->where('context_id', $contextId)
                ->first();

    if ($room) {
        return $room;
    }

    // Buat room baru
    $user1 = User::findOrFail($user1Id);
    $user2 = User::findOrFail($user2Id);
    
    $name = $user1->display_name . " & " . $user2->display_name;

    $data = new CreateRoomData(
        name: $name,
        slug: 'direct-' . Str::random(12),
        description: null,
        coverUrl: null,
        type: RoomType::Private->value,
        ownerUserId: $user1Id,
        contextType: 'direct',
        contextId: $contextId,
        settings: [
            'is_direct' => true,
            'max_members' => 2,
        ],
    );

    $room = $this->create($data);

    // Tambah user2 sebagai member
    Member::create([
        'room_id' => $room->id,
        'user_id' => $user2Id,
        'role' => 'member',
        'xp_points' => 0,
        'is_banned' => false,
        'joined_at' => now(),
        'last_read_at' => now(),
    ]);

    return $room;
}

/**
 * Get all direct chats for user
 */
public function getDirectChatsForUser(string $userId): Collection
{
    return Room::where('context_type', 'direct')
               ->where('is_active', true)
               ->whereIn('id', function ($query) use ($userId) {
                   $query->select('room_id')
                         ->from('discuss_members')
                         ->where('user_id', $userId)
                         ->where('is_banned', false);
               })
               ->with(['members'])
               ->get();
}
```

### B. Buat DirectController
Lokasi: `app/Modules/Discuss/Http/Controllers/DirectController.php`

```php
<?php

namespace App\Modules\Discuss\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Discuss\Services\RoomService;
use App\Modules\Auth\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DirectController extends Controller
{
    public function __construct(private readonly RoomService $roomService) {}

    /**
     * List semua private chat user
     */
    public function index()
    {
        $chats = $this->roomService->getDirectChatsForUser(auth()->id());
        
        return Inertia::render('Discuss/DirectList', [
            'directChats' => $chats->map(fn($room) => [
                // transform data
            ])
        ]);
    }

    /**
     * Open or create DM dengan user tertentu
     */
    public function open(Request $request, string $username)
    {
        $targetUser = User::where('username', $username)
                         ->orWhere('id', $username)
                         ->firstOrFail();

        if ($targetUser->id === auth()->id()) {
            abort(400, "Cannot chat with yourself");
        }

        $room = $this->roomService->findOrCreateDirectChat(auth()->id(), $targetUser->id);

        return redirect()->route('discuss.room', $room->slug);
    }
}
```

### C. Routes
Tambahkan di `routes/web.php` atau `app/Modules/Discuss/routes/discuss.php`:

```php
Route::middleware('auth')->prefix('direct')->name('direct.')->group(function () {
    Route::get('/', [DirectController::class, 'index'])->name('index');
    Route::post('/{username}', [DirectController::class, 'open'])->name('open');
});
```

---

## 2. Frontend Implementation

### A. Struktur File Baru
```
resources/js/Pages/Discuss/
├── DirectList.tsx
└── components/
    └── ChatListItem.tsx
```

### B. DirectList.tsx (Contoh Struktur Dasar)

```tsx
// resources/js/Pages/Discuss/DirectList.tsx
import { useState } from 'react';

export default function DirectList({ directChats }) {
  const [search, setSearch] = useState('');

  return (
    <div>
      <h1>Direct Messages</h1>
      <input 
        type="text" 
        placeholder="Search or start new chat..." 
        onChange={(e) => setSearch(e.target.value)}
      />
      
      {/* List Chat */}
      {directChats.map(room => (
        <ChatListItem key={room.id} room={room} isDirect={true} />
      ))}
    </div>
  );
}
```

### C. Update Discuss/Index.tsx
Tambahkan tab navigation:

```tsx
// Contoh
const tabs = [
  { key: 'direct', label: 'Chats' },
  { key: 'group', label: 'Groups' },
  { key: 'discover', label: 'Discover' }
];

const [activeTab, setActiveTab] = useState('direct');
```

### D. Update Room.tsx
- Tambah logic:
  ```tsx
  const isDirectChat = room.context_type === 'direct';
  ```
- Jika direct → ubah UI (hide member list, ganti title, dll)

---

## Step-by-Step Development Order

1. **Update RoomService** (Backend Core)
2. **Buat DirectController + Route**
3. **Test dengan Tinker / API**
4. **Buat DirectList.tsx (Frontend)**
5. **Integrasikan Tab di Discuss Index**
6. **Enhance Room page untuk detect direct chat**
7. **Polish & Security (Block, Privacy, dll)**

---

**Status Saat Ini**: Plan sudah matang. Siap untuk implementasi kode.

---

**Next Action:**
Ketik salah satu:
- `"Mulai Step 1"` → Update RoomService
- `"Buat DirectController"` → Langsung buat file controller
- `"Update frontend plan"` → Detail lebih dalam frontend
- `"Tampilkan full md"` → Lihat versi terbaru
