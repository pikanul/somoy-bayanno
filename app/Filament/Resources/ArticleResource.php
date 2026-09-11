<?php

namespace App\Filament\Resources;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\ArticleVisibility;
use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Tag;
use App\Models\Topic;
use App\Services\ArticleRevisionService;
use App\Services\ArticleWorkflowService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $navigationLabel = 'Articles';

    protected static ?string $modelLabel = 'Article';

    protected static ?string $pluralModelLabel = 'Articles';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $slug = 'articles';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        Select::make('type')
                            ->label('Type')
                            ->options(ArticleType::options())
                            ->default(ArticleType::Standard->value)
                            ->required()
                            ->native(false),
                        TextInput::make('headline_bn')
                            ->label('Headline')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        TextInput::make('headline_en')
                            ->label('English headline')
                            ->maxLength(255),
                        TextInput::make('short_headline_bn')
                            ->label('Short headline')
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        TextInput::make('subheadline_bn')
                            ->label('Subheadline')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('summary_bn')
                            ->label('Summary')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Content')
                    ->schema([
                        RichEditor::make('body_bn')
                            ->label('Rich text body')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Classification')
                    ->schema([
                        Select::make('primary_category_id')
                            ->label('Category')
                            ->options(fn (): array => Category::query()->orderBy('sort_order')->orderBy('name_bn')->pluck('name_bn', 'id')->all())
                            ->searchable()
                            ->required()
                            ->native(false),
                        Select::make('category_ids')
                            ->label('Additional categories')
                            ->options(fn (): array => Category::query()->orderBy('sort_order')->orderBy('name_bn')->pluck('name_bn', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->dehydrated(true),
                        Select::make('tag_ids')
                            ->label('Tags')
                            ->options(fn (): array => Tag::query()->orderBy('name_bn')->pluck('name_bn', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->dehydrated(true),
                        Select::make('topic_ids')
                            ->label('Topics')
                            ->options(fn (): array => Topic::query()->orderBy('name_bn')->pluck('name_bn', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->dehydrated(true),
                    ])
                    ->columns(2),

                Section::make('Byline')
                    ->schema([
                        Select::make('author_ids')
                            ->label('Authors')
                            ->options(fn (): array => Author::query()->orderBy('name_bn')->pluck('name_bn', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->dehydrated(true),
                        TextInput::make('reporter_name')
                            ->label('Reporter')
                            ->maxLength(255),
                        TextInput::make('location')
                            ->label('Location')
                            ->maxLength(255),
                        TextInput::make('source_name')
                            ->label('Source')
                            ->maxLength(255),
                        TextInput::make('source_url')
                            ->label('Source URL')
                            ->url()
                            ->maxLength(2048),
                    ])
                    ->columns(2),

                Section::make('Media')
                    ->schema([
                        Select::make('featured_media_id')
                            ->label('Featured media')
                            ->options(fn (): array => MediaAsset::query()
                                ->latest()
                                ->limit(100)
                                ->get()
                                ->mapWithKeys(fn (MediaAsset $mediaAsset): array => [
                                    $mediaAsset->getKey() => $mediaAsset->alt_text
                                        ?: $mediaAsset->caption
                                        ?: $mediaAsset->original_name
                                        ?: 'Media #'.$mediaAsset->getKey(),
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->native(false),
                        TextInput::make('featured_image_url')
                            ->label('External featured image')
                            ->url()
                            ->maxLength(2048),
                        TextInput::make('image_caption')
                            ->label('Image caption')
                            ->maxLength(255),
                        TextInput::make('image_credit')
                            ->label('Image credit')
                            ->maxLength(255),
                        TextInput::make('video_url')
                            ->label('Video URL')
                            ->url()
                            ->maxLength(2048),
                    ])
                    ->columns(2),

                Section::make('Publishing')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(ArticleStatus::options())
                            ->default(ArticleStatus::Draft->value)
                            ->required()
                            ->disabled()
                            ->dehydrated(true)
                            ->native(false),
                        Select::make('visibility')
                            ->label('Visibility')
                            ->options(ArticleVisibility::options())
                            ->default(ArticleVisibility::Public->value)
                            ->required()
                            ->disabled(fn (): bool => ! Auth::user()?->can('article.publish'))
                            ->dehydrated(true)
                            ->native(false),
                        Toggle::make('is_breaking')
                            ->label('Breaking')
                            ->default(false)
                            ->disabled(fn (): bool => ! Auth::user()?->can('article.publish'))
                            ->dehydrated(true),
                        Toggle::make('is_featured')
                            ->label('Featured')
                            ->default(false)
                            ->disabled(fn (): bool => ! Auth::user()?->can('article.publish'))
                            ->dehydrated(true),
                        DateTimePicker::make('scheduled_at')
                            ->label('Scheduled publish time')
                            ->seconds(false)
                            ->disabled()
                            ->dehydrated(true),
                        DateTimePicker::make('published_at')
                            ->label('Published time')
                            ->seconds(false)
                            ->disabled()
                            ->dehydrated(true),
                        Toggle::make('comments_enabled')
                            ->label('Comments enabled')
                            ->default(true),
                    ])
                    ->columns(3),

                Section::make('SEO')
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('SEO title')
                            ->maxLength(255),
                        Textarea::make('seo_description')
                            ->label('SEO description')
                            ->rows(3),
                        TextInput::make('canonical_url')
                            ->label('Canonical URL')
                            ->url()
                            ->maxLength(2048),
                        TextInput::make('social_title')
                            ->label('Social title')
                            ->maxLength(255),
                        Textarea::make('social_description')
                            ->label('Social description')
                            ->rows(3),
                        TextInput::make('social_image')
                            ->label('Social image')
                            ->url()
                            ->maxLength(2048),
                        Select::make('social_media_id')
                            ->label('Social media library image')
                            ->options(fn (): array => MediaAsset::query()
                                ->latest()
                                ->limit(100)
                                ->get()
                                ->mapWithKeys(fn (MediaAsset $mediaAsset): array => [
                                    $mediaAsset->getKey() => $mediaAsset->alt_text
                                        ?: $mediaAsset->caption
                                        ?: $mediaAsset->original_name
                                        ?: 'Media #'.$mediaAsset->getKey(),
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Editorial')
                    ->schema([
                        Textarea::make('correction_note')
                            ->label('Correction note')
                            ->rows(3),
                        Textarea::make('internal_editor_note')
                            ->label('Internal editor note')
                            ->rows(3)
                            ->visible(fn (): bool => Auth::user()?->can('article.review') || Auth::user()?->can('article.publish')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('headline_bn')
                    ->label('Headline')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                TextColumn::make('primaryCategory.name_bn')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('authors.name_bn')
                    ->label('Authors')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->toggleable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (ArticleType|string|null $state): string => $state instanceof ArticleType ? $state->label() : ucfirst((string) $state))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ArticleStatus|string|null $state): string => $state instanceof ArticleStatus ? $state->label() : ucfirst((string) $state))
                    ->sortable(),
                IconColumn::make('is_breaking')
                    ->label('Breaking')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ArticleStatus::options()),
                SelectFilter::make('type')
                    ->label('Article type')
                    ->options(ArticleType::options()),
                SelectFilter::make('primary_category_id')
                    ->label('Category')
                    ->options(fn (): array => Category::query()->orderBy('sort_order')->orderBy('name_bn')->pluck('name_bn', 'id')->all()),
                SelectFilter::make('authors')
                    ->label('Author')
                    ->relationship('authors', 'name_bn')
                    ->searchable(),
                Filter::make('published_at')
                    ->schema([
                        DatePicker::make('published_from')
                            ->label('Published from'),
                        DatePicker::make('published_until')
                            ->label('Published until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['published_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('published_at', '>=', $date))
                        ->when($data['published_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('published_at', '<=', $date))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                self::submitAction(),
                self::returnAction(),
                self::approveAction(),
                self::scheduleAction(),
                self::publishAction(),
                self::unpublishAction(),
                self::archiveAction(),
                self::revisionHistoryAction(),
                self::restoreRevisionAction(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->defaultPaginationPageOption(25)
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['primaryCategory', 'authors'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }

    private static function submitAction(): Action
    {
        return Action::make('submit')
            ->label('Submit')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->visible(fn (Article $record): bool => Auth::user()?->can('submit', $record) && in_array($record->status, [
                ArticleStatus::Draft,
                ArticleStatus::ReturnedForRevision,
            ], true))
            ->form([
                Textarea::make('comment')
                    ->label('Comment')
                    ->rows(3),
            ])
            ->action(fn (Article $record, array $data): Article => app(ArticleWorkflowService::class)->submit(Auth::user(), $record, $data['comment'] ?? null));
    }

    private static function returnAction(): Action
    {
        return Action::make('returnForRevision')
            ->label('Return')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('warning')
            ->requiresConfirmation()
            ->form([
                Textarea::make('comment')
                    ->label('Reason')
                    ->required()
                    ->rows(3),
            ])
            ->visible(fn (Article $record): bool => Auth::user()?->can('returnForRevision', $record) && in_array($record->status, [
                ArticleStatus::PendingReview,
                ArticleStatus::Approved,
            ], true))
            ->action(fn (Article $record, array $data): Article => app(ArticleWorkflowService::class)->returnForRevision(Auth::user(), $record, $data['comment']));
    }

    private static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->form([
                Textarea::make('comment')
                    ->label('Comment')
                    ->rows(3),
            ])
            ->visible(fn (Article $record): bool => Auth::user()?->can('approve', $record) && $record->status === ArticleStatus::PendingReview)
            ->action(fn (Article $record, array $data): Article => app(ArticleWorkflowService::class)->approve(Auth::user(), $record, $data['comment'] ?? null));
    }

    private static function scheduleAction(): Action
    {
        return Action::make('schedule')
            ->label('Schedule')
            ->icon(Heroicon::OutlinedClock)
            ->color('info')
            ->requiresConfirmation()
            ->form([
                DateTimePicker::make('scheduled_at')
                    ->label('Scheduled publish time')
                    ->required()
                    ->seconds(false),
                Textarea::make('comment')
                    ->label('Comment')
                    ->rows(3),
            ])
            ->visible(fn (Article $record): bool => Auth::user()?->can('schedule', $record) && $record->status === ArticleStatus::Approved)
            ->action(fn (Article $record, array $data): Article => app(ArticleWorkflowService::class)->schedule(Auth::user(), $record, $data['scheduled_at'], $data['comment'] ?? null));
    }

    private static function publishAction(): Action
    {
        return Action::make('publish')
            ->label('Publish')
            ->icon(Heroicon::OutlinedRocketLaunch)
            ->color('success')
            ->requiresConfirmation()
            ->form([
                Textarea::make('comment')
                    ->label('Comment')
                    ->rows(3),
            ])
            ->visible(fn (Article $record): bool => Auth::user()?->can('publish', $record) && in_array($record->status, [
                ArticleStatus::Approved,
                ArticleStatus::Scheduled,
                ArticleStatus::Unpublished,
            ], true))
            ->action(fn (Article $record, array $data): Article => app(ArticleWorkflowService::class)->publish(Auth::user(), $record, $data['comment'] ?? null));
    }

    private static function unpublishAction(): Action
    {
        return Action::make('unpublish')
            ->label('Unpublish')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->requiresConfirmation()
            ->form([
                Textarea::make('comment')
                    ->label('Reason')
                    ->required()
                    ->rows(3),
            ])
            ->visible(fn (Article $record): bool => Auth::user()?->can('unpublish', $record) && in_array($record->status, [
                ArticleStatus::Published,
                ArticleStatus::Updated,
                ArticleStatus::Scheduled,
            ], true))
            ->action(fn (Article $record, array $data): Article => app(ArticleWorkflowService::class)->unpublish(Auth::user(), $record, $data['comment']));
    }

    private static function archiveAction(): Action
    {
        return Action::make('archive')
            ->label('Archive')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('gray')
            ->requiresConfirmation()
            ->form([
                Textarea::make('comment')
                    ->label('Reason')
                    ->required()
                    ->rows(3),
            ])
            ->visible(fn (Article $record): bool => Auth::user()?->can('archive', $record) && $record->status !== ArticleStatus::Archived)
            ->action(fn (Article $record, array $data): Article => app(ArticleWorkflowService::class)->archive(Auth::user(), $record, $data['comment']));
    }

    private static function revisionHistoryAction(): Action
    {
        return Action::make('revisionHistory')
            ->label('Revisions')
            ->icon(Heroicon::OutlinedClock)
            ->modalHeading(fn (Article $record): string => "Revision history: {$record->headline_bn}")
            ->modalSubmitAction(false)
            ->modalContent(fn (Article $record) => view('filament.article-revisions.history', [
                'article' => $record,
                'revisions' => $record->revisions()->with(['changedBy', 'actor'])->latest('created_at')->get(),
                'revisionService' => app(ArticleRevisionService::class),
            ]))
            ->visible(fn (Article $record): bool => Auth::user()?->can('view', $record));
    }

    private static function restoreRevisionAction(): Action
    {
        return Action::make('restoreRevision')
            ->label('Restore Revision')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->requiresConfirmation()
            ->form(fn (Article $record): array => [
                Select::make('revision_id')
                    ->label('Version')
                    ->options(fn (): array => $record->revisions()
                        ->orderByDesc('version')
                        ->get()
                        ->mapWithKeys(fn ($revision): array => [
                            $revision->getKey() => 'Version '.($revision->version ?? $revision->revision_number).' - '.($revision->change_summary ?? 'No summary'),
                        ])
                        ->all())
                    ->required()
                    ->native(false),
                Textarea::make('reason')
                    ->label('Reason')
                    ->required()
                    ->rows(3),
            ])
            ->visible(fn (Article $record): bool => Auth::user()?->can('restoreRevision', $record))
            ->action(function (Article $record, array $data): Article {
                $revision = $record->revisions()->whereKey($data['revision_id'])->firstOrFail();

                return app(ArticleRevisionService::class)->restore(Auth::user(), $revision, $data['reason']);
            });
    }
}
