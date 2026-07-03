<?php

namespace App\Modules\Discuss\Http\Controllers;

use App\Modules\Discuss\Models\Member;
use App\Modules\Discuss\Models\Room;
use App\Modules\Discuss\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController
{
    public function __construct(
        private readonly MemberService $memberService,
    ) {}

    public function store(string $slug): JsonResponse
    {
        $room = Room::where('slug', $slug)->firstOrFail();

        $member = $this->memberService->join($room->id, request()->user()->id);

        return response()->json(['message' => 'Joined room.'], 201);
    }

    public function destroy(string $slug): JsonResponse
    {
        $room = Room::where('slug', $slug)->firstOrFail();

        $this->memberService->leave($room->id, request()->user()->id);

        return response()->json(['message' => 'Left room.']);
    }

    public function kick(string $slug, string $memberId): JsonResponse
    {
        $member = Member::findOrFail($memberId);

        $this->memberService->kick($member, request()->user()->id);

        return response()->json(['message' => 'Member kicked.']);
    }

    public function mute(Request $request, string $slug, string $memberId): JsonResponse
    {
        $request->validate(['minutes' => ['required', 'integer', 'min:1']]);

        $member = Member::findOrFail($memberId);

        $this->memberService->mute($member, $request->input('minutes'), $request->user()->id);

        return response()->json(['message' => 'Member muted.']);
    }

    public function ban(string $slug, string $memberId): JsonResponse
    {
        $member = Member::findOrFail($memberId);

        $this->memberService->ban($member, request()->user()->id);

        return response()->json(['message' => 'Member banned.']);
    }
}
