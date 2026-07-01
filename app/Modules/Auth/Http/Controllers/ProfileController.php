<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Data\UserData;
use App\Modules\Auth\Http\Requests\UpdateProfileRequest;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\UserProfile;
use App\Models\Bookmark;
use App\Modules\Discuss\Models\Message;
use App\Modules\Discuss\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileController
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => UserData::fromModel($request->user()),
        ]);
    }

    public function edit(Request $request): \Inertia\Response
    {
        $user = $request->user();

        $profile = UserProfile::where('user_id', $user->id)->first();

        $stats = [
            'bookmarks' => Bookmark::where('user_id', $user->id)->count(),
            'messages'  => Message::where('user_id', $user->id)->count(),
            'rooms'     => Member::where('user_id', $user->id)->count(),
            'xp'        => Member::where('user_id', $user->id)->sum('xp_points'),
            'member_since' => $user->created_at?->format('M Y'),
        ];

        return Inertia::render('Profile', [
            'profile' => $profile ? $profile->only(['bio', 'website_url', 'location']) : null,
            'stats'   => $stats,
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar_url' => Storage::url($path)]);
        }

        $user->update($request->only(['display_name', 'username']));

        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            $request->only(['bio', 'website_url', 'location', 'preferences']),
        );

        return response()->json([
            'message' => 'Profile updated.',
            'user' => UserData::fromModel($user->fresh()),
        ]);
    }
}
