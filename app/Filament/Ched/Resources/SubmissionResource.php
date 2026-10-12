<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\Submission;
use Carbon\CarbonImmutable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubmissionResource extends ScopedResource
{
    protected static ?string $model = Submission::class;

    protected static ?string $createPermission = null;

    protected static string $readPermission = 'records.view';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withMin('activityVersions', 'starts_at');
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)->pushColumns([
            TextColumn::make('activity_versions_min_starts_at')->label('First filed activity (Asia/Manila)')
                ->dateTime('d M Y, H:i', timezone: config('lakbay.timezone'))->sortable()->placeholder('No filed activity'),
            TextColumn::make('pre_conduct_attention')->label('Review attention')->state(function (Submission $record): string {
                if (! in_array($record->status, ['submitted', 'under_review', 'corrections_requested'], true)) {
                    return 'No open review';
                }
                if (! $record->activity_versions_min_starts_at) {
                    return 'Check filed schedule';
                }
                $pending = $record->status === 'corrections_requested' ? 'Corrections outstanding' : 'Review pending';

                return $pending.(CarbonImmutable::parse($record->activity_versions_min_starts_at)->lte(now()) ? ' — filed start reached' : ' — before filed start');
            })->wrap()->description('Operational cue; not a legal review deadline or permission to conduct.'),
        ])->pushFilters([
            Filter::make('pre_conduct_attention')->label('Open review / outstanding corrections')
                ->query(fn (Builder $query): Builder => $query->whereIn('status', ['submitted', 'under_review', 'corrections_requested'])),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSubmission::route('/'), 'view' => Pages\ViewSubmission::route('/{record}')];
    }
}
