<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Models\Article;
use App\Services\ArticleAdministrationService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Article $record */
        $record = $this->getRecord();

        $data['category_ids'] = $record->categories()->pluck('categories.id')->all();
        $data['tag_ids'] = $record->tags()->pluck('tags.id')->all();
        $data['topic_ids'] = $record->topics()->pluck('topics.id')->all();
        $data['author_ids'] = $record->authors()->pluck('authors.id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(ArticleAdministrationService::class)->update(Auth::user(), $record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
