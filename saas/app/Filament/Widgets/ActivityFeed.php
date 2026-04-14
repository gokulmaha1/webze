<?php

namespace App\Filament\Widgets;

use App\Models\Website;
use App\Models\Transaction;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ActivityFeed extends BaseWidget
{
    protected static ?int $sort = 5;
    protected static ?string $heading = 'Real-Time Activity Feed';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        // For a true feed, we'd use a unified log or spatie/activitylog. 
        // For now, we simulate a feed by merging recent items from different models 
        // into a single collection, mapping them, and wrapping it in a simple array-based table
        // Or simply display the 5 most recently created Websites in real-time.

        return $table
            ->query(
                Website::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->description(fn (Website $record): string => $record->created_at->diffForHumans()),
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Event')
                    ->formatStateUsing(fn ($state) => "New website generated for {$state}")
                    ->icon('heroicon-o-globe-alt')
                    ->color('success'),
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge(),
                Tables\Columns\TextColumn::make('template.name')
                    ->label('Assigned Template'),
            ])
            ->paginated(false);
    }
}
