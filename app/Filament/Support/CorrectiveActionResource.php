<?php

namespace App\Filament\Support;

use App\Actions\Operations\ManageCorrectiveAction;
use App\Models\CorrectiveAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

abstract class CorrectiveActionResource extends ScopedResource
{
    protected static ?string $model = CorrectiveAction::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components(array_map(fn ($f) => TextEntry::make($f)->wrap(), ['institution.name', 'action', 'responsible_party', 'due_on', 'status', 'response']));
    }

    public static function table(Table $table): Table
    {
        return $table->columns(array_map(fn ($f) => TextColumn::make($f)->wrap(), ['institution.name', 'action', 'due_on', 'status']))->recordActions([
            ViewAction::make(),
            Action::make('respond')->visible(fn ($record) => auth()->user()->can('reports.submit') && $record->status !== 'closed')->schema([Textarea::make('response')->required()])->action(fn ($record, array $data) => app(ManageCorrectiveAction::class)->respond(auth()->user(), $record, $data['response'])),
            Action::make('close')->requiresConfirmation()->visible(fn ($record) => auth()->user()->can('monitoring.manage') && $record->status === 'responded')->action(fn ($record) => app(ManageCorrectiveAction::class)->close(auth()->user(), $record)),
        ]);
    }
}
