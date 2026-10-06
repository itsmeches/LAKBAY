<?php

namespace App\Filament\Ched\Pages;

use App\Actions\Identity\InviteUser;
use App\Filament\Support\Fields;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Invitations extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.invitations';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('users.invite') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('invite')->schema([
            TextInput::make('name')->required(), TextInput::make('email')->email()->required(), TextInput::make('position')->required(),
            Select::make('role')->options(['hei_preparer' => 'HEI preparer', 'hei_submitter' => 'HEI authorized submitter', 'ched_records' => 'CHED records', 'ched_reviewer' => 'CHED reviewer', 'ched_signatory' => 'CHED signatory', 'ched_monitor' => 'CHED monitor', 'ched_case_officer' => 'Restricted case officer', 'ched_analyst' => 'Analyst', 'system_administrator' => 'System administrator'])->required(),
            Fields::institution()->required(false),
        ])->action(function (array $data) {
            app(InviteUser::class)->execute(auth()->user(), $data);
            Notification::make()->title('Invitation sent')->success()->send();
        })];
    }
}
