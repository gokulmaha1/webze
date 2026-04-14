<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebsiteResource\Pages;
use App\Models\Template;
use App\Models\Website;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class WebsiteResource extends Resource
{
    protected static ?string $model = Website::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business Info')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $slug = Str::slug($state) . '-' . time();
                                $set('slug', $slug);
                                $set('url', 'https://' . $slug . '.webze.site');
                            }),

                        Forms\Components\TextInput::make('owner_name')
                            ->nullable()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('category')
                            ->nullable()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->nullable()
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\Textarea::make('address')
                            ->nullable()
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Site Settings')
                    ->schema([
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                $set('url', 'https://' . $state . '.webze.site')
                            ),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'live'  => 'Live',
                                'paid'  => 'Paid',
                            ])
                            ->default('draft')
                            ->required(),

                        Forms\Components\Select::make('template_id')
                            ->label('Template')
                            ->options(Template::pluck('name', 'id'))
                            ->nullable()
                            ->searchable(),

                        Forms\Components\TextInput::make('url')
                            ->nullable()
                            ->url()
                            ->maxLength(500),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('AI Content')
                    ->schema([
                        Forms\Components\Textarea::make('ai_content')
                            ->label('Raw AI Content (JSON)')
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                            ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                            ->rows(15)
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Business Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone'),
                Tables\Columns\TextColumn::make('whatsappLogs_count')
                    ->counts('whatsappLogs')
                    ->label('Clicks')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'  => 'warning',
                        'live'   => 'success',
                        'paid'   => 'primary',
                        default  => 'gray',
                    }),
                Tables\Columns\TextColumn::make('url')
                    ->label('Live URL')
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab()
                    ->copyable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'live'  => 'Live',
                        'paid'  => 'Paid',
                    ]),
                Tables\Filters\SelectFilter::make('template_id')
                    ->label('Template')
                    ->relationship('template', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('regenerate')
                    ->label('Regenerate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Website $record) => $record->generateStaticSite()),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListWebsites::route('/'),
            'create' => Pages\CreateWebsite::route('/create'),
            'edit'   => Pages\EditWebsite::route('/{record}/edit'),
        ];
    }
}
