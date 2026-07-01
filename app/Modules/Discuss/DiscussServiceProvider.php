<?php

namespace App\Modules\Discuss;

use App\Modules\Discuss\Events\MessageSent;
use App\Modules\Discuss\Listeners\AwardXpOnMessage;
use App\Modules\Discuss\Listeners\CheckRankPromotion;
use App\Modules\Discuss\Listeners\SendMentionNotification;
use App\Modules\Discuss\Listeners\SendReplyNotification;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class DiscussServiceProvider extends ServiceProvider
{
    protected $listen = [
        MessageSent::class => [
            AwardXpOnMessage::class,
            CheckRankPromotion::class,
            SendMentionNotification::class,
            SendReplyNotification::class,
        ],
    ];

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/discuss.php');
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        require __DIR__ . '/Channels/RoomChannel.php';
    }
}
