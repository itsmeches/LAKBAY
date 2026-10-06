<?php

namespace App\Filament\Ched\Pages;

use App\Models\Activity;
use App\Models\PolicyVersion;
use App\Models\Submission;
use App\Services\Access;
use Filament\Pages\Page;

class Overview extends Page
{
    protected static ?string $navigationLabel = 'Start here';

    protected static ?int $navigationSort = -10;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public function getTitle(): string
    {
        return 'Your workspace';
    }

    protected string $view = 'filament.pages.overview';

    protected function getViewData(): array
    {
        $user = auth()->user();
        $access = app(Access::class);

        return ['draftPolicy' => PolicyVersion::where('status', 'confirmed')->doesntExist(), 'activities' => $access->scope(Activity::query(), $user)->count(), 'submissions' => $access->scope(Submission::query(), $user)->where('status', '!=', 'superseded')->count(), 'corrections' => $access->scope(Submission::query(), $user)->where('status', 'corrections_requested')->count(), 'panel' => 'ched', 'reviewPending' => $access->scope(Submission::query(), $user)->whereIn('status', ['submitted', 'under_review'])->count(), 'ackPending' => $access->scope(Submission::query(), $user)->where('status', 'ready_for_acknowledgment')->count(), 'recent' => $access->scope(Submission::query(), $user)->latest()->limit(10)->get()];
    }
}
