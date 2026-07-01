<?php

namespace App\Modules\Discuss\Database\Seeders;

use App\Modules\Discuss\Models\Emote;
use Illuminate\Database\Seeder;

class DefaultEmotesSeeder extends Seeder
{
    public function run(): void
    {
        $emotes = [
            ['code' => ':like:', 'name' => 'Like', 'image_url' => '/emotes/like.png', 'is_animated' => false],
            ['code' => ':love:', 'name' => 'Love', 'image_url' => '/emotes/love.png', 'is_animated' => false],
            ['code' => ':laugh:', 'name' => 'Laugh', 'image_url' => '/emotes/laugh.png', 'is_animated' => false],
            ['code' => ':wow:', 'name' => 'Wow', 'image_url' => '/emotes/wow.png', 'is_animated' => false],
            ['code' => ':sad:', 'name' => 'Sad', 'image_url' => '/emotes/sad.png', 'is_animated' => false],
        ];

        foreach ($emotes as $emote) {
            Emote::create($emote);
        }
    }
}
