<?php

namespace App\Enums;

enum ArticleWorkflowAction: string
{
    case Submit = 'submit';
    case Return = 'return';
    case Approve = 'approve';
    case Schedule = 'schedule';
    case Publish = 'publish';
    case Unpublish = 'unpublish';
    case Archive = 'archive';

    public function label(): string
    {
        return match ($this) {
            self::Submit => 'Submit',
            self::Return => 'Return for Revision',
            self::Approve => 'Approve',
            self::Schedule => 'Schedule',
            self::Publish => 'Publish',
            self::Unpublish => 'Unpublish',
            self::Archive => 'Archive',
        };
    }
}
