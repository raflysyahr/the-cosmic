<?php

namespace App\Modules\Discuss\Enums;

enum MessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case Sticker = 'sticker';
    case System = 'system';
}
