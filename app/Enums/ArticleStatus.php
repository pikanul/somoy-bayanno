<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case ReturnedForRevision = 'returned_for_revision';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Updated = 'updated';
    case Unpublished = 'unpublished';
    case Archived = 'archived';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingReview => 'Pending Review',
            self::ReturnedForRevision => 'Returned for Revision',
            self::Approved => 'Approved',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Updated => 'Updated',
            self::Unpublished => 'Unpublished',
            self::Archived => 'Archived',
            self::Rejected => 'Rejected',
        };
    }

    /** @return array<int, self> */
    public static function publiclyReadable(): array
    {
        return [
            self::Published,
            self::Updated,
        ];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::Draft->value => self::Draft->label(),
            self::PendingReview->value => self::PendingReview->label(),
            self::ReturnedForRevision->value => self::ReturnedForRevision->label(),
            self::Approved->value => self::Approved->label(),
            self::Scheduled->value => self::Scheduled->label(),
            self::Published->value => self::Published->label(),
            self::Updated->value => self::Updated->label(),
            self::Unpublished->value => self::Unpublished->label(),
            self::Archived->value => self::Archived->label(),
            self::Rejected->value => self::Rejected->label(),
        ];
    }
}
