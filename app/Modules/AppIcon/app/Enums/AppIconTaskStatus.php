<?php

namespace Modules\AppIcon\Enums;

enum AppIconTaskStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
