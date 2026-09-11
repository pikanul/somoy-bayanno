<?php

namespace App\Enums;

enum HomepageSectionKey: string
{
    case MainLead = 'main_lead';
    case SecondaryLeadOne = 'secondary_lead_1';
    case SecondaryLeadTwo = 'secondary_lead_2';
    case TopStories = 'top_stories';
    case EditorsChoice = 'editors_choice';
    case SpecialReport = 'special_report';
    case LatestHighlight = 'latest_highlight';
    case VideoHighlight = 'video_highlight';
    case PhotoHighlight = 'photo_highlight';
    case CategoryFeatured = 'category_featured';

    public function label(): string
    {
        return match ($this) {
            self::MainLead => 'Main Lead',
            self::SecondaryLeadOne => 'Secondary Lead 1',
            self::SecondaryLeadTwo => 'Secondary Lead 2',
            self::TopStories => 'Top Stories',
            self::EditorsChoice => "Editor's Choice",
            self::SpecialReport => 'Special Report',
            self::LatestHighlight => 'Latest Highlight',
            self::VideoHighlight => 'Video Highlight',
            self::PhotoHighlight => 'Photo Highlight',
            self::CategoryFeatured => 'Category Featured',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }

    /** @return array<int, string> */
    public static function singleArticleSlots(): array
    {
        return [
            self::MainLead->value,
            self::SecondaryLeadOne->value,
            self::SecondaryLeadTwo->value,
            self::LatestHighlight->value,
            self::VideoHighlight->value,
            self::PhotoHighlight->value,
        ];
    }
}
