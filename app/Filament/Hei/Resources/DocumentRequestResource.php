<?php

namespace App\Filament\Hei\Resources;

use App\Actions\Documents\RequestEvidence;
use App\Filament\Support\Fields;
use App\Filament\Support\ScopedResource;
use App\Models\Document;
use App\Models\DocumentRequest;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentRequestResource extends ScopedResource
{
    protected static ?string $model = DocumentRequest::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components(array_map(fn ($f) => TextEntry::make($f), ['submission.reference', 'documentType.name', 'reason', 'due_at', 'status']));
    }

    public static function table(Table $table): Table
    {
        return $table->columns(array_map(fn ($f) => TextColumn::make($f)->wrap(), ['submission.reference', 'documentType.name', 'due_at', 'status']))->recordActions([
            Action::make('respond')->visible(fn ($record) => auth()->user()->can('submissions.submit') && $record->status === 'requested')->schema(fn ($record) => [Select::make('document_id')->options(fn () => Fields::related(Document::class, 'original_name', $record->institution_id))->required()])->action(fn ($record, array $data) => app(RequestEvidence::class)->respond(auth()->user(), $record, (int) $data['document_id'])),
            ViewAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListDocumentRequest::route('/'), 'view' => Pages\ViewDocumentRequest::route('/{record}')];
    }
}
