<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Modules\Auth\AuthServiceProvider::class,
    App\Modules\Discuss\DiscussServiceProvider::class,
];
