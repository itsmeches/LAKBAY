<?php

namespace App\Filament\Support;

use App\Models\Activity;
use App\Models\ActivityVersion;
use App\Models\Document;
use App\Models\Institution;
use App\Services\Access;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

class Fields
{
    public static function institutions(): array
    {
        return app(Access::class)->scope(Institution::query(), auth()->user(), 'id')->pluck('name', 'id')->all();
    }

    public static function related(string $model, string $label = 'title', ?int $institution = null): array
    {
        if ($model === ActivityVersion::class) {
            return $model::whereIn('activity_id', app(Access::class)->scope(Activity::query(), auth()->user())->when($institution, fn ($q) => $q->where('institution_id', $institution))->select('id'))->get()->mapWithKeys(fn ($v) => [$v->id => $v->title.' / revision '.$v->version])->all();
        }
        $q = app(Access::class)->scope($model::query(), auth()->user())->when($institution, fn ($q) => $q->where('institution_id', $institution));
        if ($model === Document::class) {
            return $q->where('scan_status', 'clean')->get()->filter(fn ($d) => app(Access::class)->document(auth()->user(), $d))->mapWithKeys(fn ($d) => [$d->id => $d->original_name.' (#'.$d->id.')'])->all();
        }

        return $q->pluck($label, 'id')->all();
    }

    public static function institution()
    {
        return Select::make('institution_id')->options(fn () => self::institutions())->required()->live();
    }

    public static function relation(string $key, string $model, string $label = 'title', bool $required = true)
    {
        return Select::make($key)->options(fn (Get $get) => self::related($model, $label, $get('institution_id') ? (int) $get('institution_id') : null))->searchable()->required($required);
    }

    public static function make(string $name, string|array $type)
    {
        if (is_array($type)) {
            return Select::make($name)->options($type)->required();
        }

        return match ($type) {
            'text' => TextInput::make($name)->required()->maxLength(255),
            'optional' => TextInput::make($name)->maxLength(255),
            'area' => Textarea::make($name)->required()->columnSpanFull(),
            'optional_area' => Textarea::make($name)->columnSpanFull(),
            'date' => DatePicker::make($name)->required(),
            'optional_date' => DatePicker::make($name),
            'datetime' => DateTimePicker::make($name)->helperText('Asia/Manila time')->required(),
            'number' => TextInput::make($name)->numeric()->minValue(0)->required(),
            'boolean' => Toggle::make($name)->default(false),
            default => throw new \InvalidArgumentException('Unknown form field')
        };
    }
}
