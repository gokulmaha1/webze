<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class RecentTransactions extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Transactions')
            ->query(
                Transaction::query()->latest()->limit(5)
            )
            ->isCompact()
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->default('Guest'),

                Tables\Columns\TextColumn::make('plan')
                    ->label('Plan')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'yearly'     => 'success',
                        'monthly'    => 'primary',
                        'one_time'   => 'info',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'N/A')),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid'    => 'success',
                        'pending' => 'warning',
                        'failed'  => 'danger',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('cashfree_payment_status')
                    ->label('Gateway Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'SUCCESS' => 'success',
                        'FAILED'  => 'danger',
                        'PENDING' => 'warning',
                        default   => 'gray',
                    })
                    ->default('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
}
