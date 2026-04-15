<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon  = 'heroicon-o-currency-rupee';
    protected static ?string $navigationGroup = 'Sales & Analytics';
    protected static ?string $navigationLabel = 'Transactions';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Transaction Details')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->required()
                        ->searchable()
                        ->label('Customer'),

                    Forms\Components\TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->prefix('₹')
                        ->label('Amount (INR)'),

                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'paid'    => 'Paid',
                            'failed'  => 'Failed',
                        ])
                        ->required(),

                    Forms\Components\Select::make('plan')
                        ->options([
                            'monthly'  => 'Monthly',
                            'yearly'   => 'Yearly',
                            'one_time' => 'One Time',
                        ])
                        ->nullable()
                        ->label('Plan'),
                ]),

            Forms\Components\Section::make('Cashfree Payment Details')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('cashfree_order_id')
                        ->label('Cashfree Order ID')
                        ->nullable()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('cashfree_payment_id')
                        ->label('Cashfree Payment ID')
                        ->nullable()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('cashfree_payment_status')
                        ->label('Payment Status (Cashfree)')
                        ->nullable()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('currency')
                        ->default('INR')
                        ->maxLength(10),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('INR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('plan')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state ?? 'N/A'))),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'    => 'success',
                        'pending' => 'warning',
                        'failed'  => 'danger',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('cashfree_order_id')
                    ->label('Cashfree Order ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cashfree_payment_id')
                    ->label('Payment ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'paid'    => 'Paid',
                        'pending' => 'Pending',
                        'failed'  => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('plan')
                    ->options([
                        'monthly'  => 'Monthly',
                        'yearly'   => 'Yearly',
                        'one_time' => 'One Time',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit'   => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
