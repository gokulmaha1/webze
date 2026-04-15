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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

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
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('insights')
                    ->label('Insights')
                    ->html()
                    ->getStateUsing(function (Website $record): HtmlString {
                        $user = $record->user_clicks_count ?? 0;
                        $admin = $record->admin_clicks_count ?? 0;
                        $google = $record->google_clicks_count ?? 0;
                        $wa = $record->whatsapp_clicks_count ?? 0;

                        return new HtmlString("
                            <div class='flex items-center gap-3 text-xs'>
                                <div class='flex items-center gap-1' title='User Clicks'><span class='text-primary-500'>👤</span> <b>{$user}</b></div>
                                <div class='flex items-center gap-1 opacity-50' title='Admin Clicks'><span>🛠️</span> {$admin}</div>
                                <div class='flex items-center gap-1' title='Google Traffic'><span class='text-info-500'>🔍</span> <b>{$google}</b></div>
                                <div class='flex items-center gap-1' title='WhatsApp Traffic'><span class='text-success-500'>📱</span> <b>{$wa}</b></div>
                            </div>
                        ");
                    })
                    ->sortable(['user_clicks_count']),
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
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab()
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
                Tables\Actions\Action::make('notify')
                    ->label('Notify')
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->color('success')
                    ->url(fn (Website $record): string => "https://wa.me/" . preg_replace('/[^0-9]/', '', $record->phone) . "?text=" . urlencode("Hi {$record->business_name},\n\nYour new professional website is live and ready! 🚀\n\nCheck it out here: https://app.webze.site/v/{$record->slug}\n\nLet us know what you think!"))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('request_payment')
                    ->label('Request Payment')
                    ->icon('heroicon-o-currency-rupee')
                    ->color('primary')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Invoice Amount (INR)')
                            ->numeric()
                            ->default(1999)
                            ->required(),
                        Forms\Components\TextInput::make('purpose')
                            ->label('Purpose')
                            ->default('One-time website activation fee')
                            ->required(),
                    ])
                    ->action(function (Website $record, array $data, \App\Services\CashfreeService $cashfree) {
                        // 1. Create a transaction record
                        $transaction = \App\Models\Transaction::create([
                            'user_id'    => $record->user_id ?? \App\Models\User::first()->id,
                            'website_id' => $record->id,
                            'amount'     => $data['amount'],
                            'status'     => 'pending',
                            'plan'       => 'one_time',
                            'currency'   => 'INR',
                        ]);

                        // 2. Generate a unique Cashfree Order ID
                        $cashfreeOrderId = \App\Services\CashfreeService::generateOrderId($transaction->id);

                        // 3. Create Order via Cashfree (standard PG)
                        $orderResponse = $cashfree->createOrder(
                            orderId:       $cashfreeOrderId,
                            amount:        (float) $data['amount'],
                            customerName:  $record->business_name,
                            customerEmail: 'customer@webze.site',
                            customerPhone: preg_replace('/[^0-9]/', '', $record->phone),
                            returnUrl:     route('payment.callback')
                        );

                        // 4. Update transaction with order details
                        $transaction->update([
                            'cashfree_order_id'           => $cashfreeOrderId,
                            'cashfree_payment_session_id' => $orderResponse['payment_session_id'],
                        ]);

                        // 5. Send to WhatsApp (UNIFIED MESSAGE with SHORT LINKS)
                        $shortVisitUrl = "https://app.webze.site/v/{$record->slug}";
                        $shortPayUrl   = "https://app.webze.site/p/{$cashfreeOrderId}";

                        $waMessage = urlencode("Hi {$record->business_name}, your professional website is live! 🚀\n\n🌐 Preview: {$shortVisitUrl}\n💳 Activate: {$shortPayUrl}\n\nActivate now to remove trial limits and keep your site live forever!");

                        return redirect()->away("https://wa.me/" . preg_replace('/[^0-9]/', '', $record->phone) . "?text={$waMessage}");
                    }),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('regenerate')
                        ->label('Regenerate')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Website $record) {
                            if (!$record->category && isset($record->ai_content)) {
                                $cat = $record->ai_content['category'] ?? null;
                                if ($cat) {
                                    $record->category = $cat;
                                    $record->save();
                                }
                            }
                            $guessedId = $record->guessTemplateId($record->category, $record->business_name, $record->ai_content);
                            if ($guessedId && $record->template_id != $guessedId) {
                                $record->template_id = $guessedId;
                                $record->save();
                                $record->refresh();
                            }
                            $record->generateStaticSite();
                        }),
                    Tables\Actions\DeleteAction::make(),
                ]),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount([
                'analyticsLogs as user_clicks_count' => fn (Builder $query) => $query->where('is_admin_visit', false),
                'analyticsLogs as admin_clicks_count' => fn (Builder $query) => $query->where('is_admin_visit', true),
                'analyticsLogs as google_clicks_count' => fn (Builder $query) => $query->where('metadata->source', 'google'),
                'analyticsLogs as whatsapp_clicks_count' => fn (Builder $query) => $query->whereIn('metadata->source', ['whatsapp_share', 'whatsapp_notification']),
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
