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
                Tables\Actions\Action::make('launch_and_share')
                    ->label(fn (Website $record) => $record->status === 'paid' ? 'Share Site' : 'Launch & Invoice')
                    ->icon(fn (Website $record) => $record->status === 'paid' ? 'heroicon-o-share' : 'heroicon-o-rocket-launch')
                    ->color(fn (Website $record) => $record->status === 'paid' ? 'success' : 'primary')
                    ->form(fn (Website $record) => $record->status === 'paid' ? [] : [
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
                        $shortVisitUrl = "https://app.webze.site/v/{$record->slug}";
                        
                        // IF ALREADY PAID: Just share the link
                        if ($record->status === 'paid') {
                            $waMessage = urlencode("Hi {$record->business_name},\n\nYour professional website is active and live! 🚀\n\nYou can access it anytime here: {$shortVisitUrl}\n\nThank you for choosing Webze!");
                            return redirect()->away("https://wa.me/" . preg_replace('/[^0-9]/', '', $record->phone) . "?text={$waMessage}");
                        }

                        // IF NOT PAID: Invoice + Launch logic
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

                        // 3. Create Order via Cashfree
                        $orderResponse = $cashfree->createOrder(
                            orderId:       $cashfreeOrderId,
                            amount:        (float) $data['amount'],
                            customerName:  $record->business_name,
                            customerEmail: 'customer@webze.site',
                            customerPhone: preg_replace('/[^0-9]/', '', $record->phone),
                            returnUrl:     route('payment.callback')
                        );

                        // 4. Update transaction
                        $transaction->update([
                            'cashfree_order_id'           => $cashfreeOrderId,
                            'cashfree_payment_session_id' => $orderResponse['payment_session_id'],
                        ]);

                        // 5. Send UNIFIED MESSAGE
                        $shortPayUrl = "https://app.webze.site/p/{$cashfreeOrderId}";
                        $waMessage   = urlencode("Hi {$record->business_name}, your professional website is live! 🚀\n\n🌐 Preview: {$shortVisitUrl}\n💳 Activate: {$shortPayUrl}\n\nActivate now to remove trial limits and keep your site live forever!");

                        return redirect()->away("https://wa.me/" . preg_replace('/[^0-9]/', '', $record->phone) . "?text={$waMessage}");
                    }),
                Tables\Actions\Action::make('manual_share')
                    ->label('Manual Share (GPAY)')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('tier')
                            ->label('Select Strategy Tier')
                            ->options([
                                'small'     => 'Small (Tea shops, salons...) - ₹2,999',
                                'growing'   => 'Growing (Clinics, hotels...) - ₹3,999',
                                'premium'   => 'Premium (Builders, real estate...) - ₹9,999',
                                'ecommerce' => 'Ecommerce (Wholesalers, sellers...) - ₹14,999',
                            ])
                            ->default('small')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $prices = [
                                    'small'     => 2999,
                                    'growing'   => 3999,
                                    'premium'   => 9999,
                                    'ecommerce' => 14999,
                                ];
                                $set('price', $prices[$state] ?? 2999);
                            }),
                        Forms\Components\TextInput::make('price')
                            ->label('Quoted Price (INR)')
                            ->numeric()
                            ->default(2999)
                            ->required(),
                    ])
                    ->action(function (Website $record, array $data) {
                        $visitUrl = "https://app.webze.site/visit/{$record->slug}?source=whatsapp_share";
                        $price    = $data['price'];
                        $tier     = $data['tier'];

                        // Define strategy-based messages
                        $templates = [
                            'small' => [
                                'intro'   => "Your professional 1-page business website is now LIVE 🚀",
                                'offers'  => "✅ Online presence\n✅ WhatsApp button for customers\n✅ Easy sharing on social media",
                                'benefit' => "Build trust with a branded site and get more customers online.",
                                'cta'     => "Activate now for ₹{$price}!",
                            ],
                            'growing' => [
                                'intro'   => "Your professional multi-page business website is now LIVE 🚀",
                                'offers'  => "✅ 3–5 pages with custom content\n✅ Your own domain (yourname.com)\n✅ Direct WhatsApp leads from customers\n✅ Google Search visibility",
                                'benefit' => "Take your business to the next level with professional web presence and more enquiries.",
                                'cta'     => "Get more enquiries now! Activate for ₹{$price}.",
                            ],
                            'premium' => [
                                'intro'   => "Your premium business portal and lead system is now LIVE 🚀",
                                'offers'  => "✅ Full professional website\n✅ Advanced lead generation system\n✅ Professional branding & SEO\n✅ Custom features & integrations",
                                'benefit' => "Establish market authority and generate high-quality business leads daily.",
                                'cta'     => "Start generating leads now! Activate for ₹{$price}.",
                            ],
                            'ecommerce' => [
                                'intro'   => "Your professional Ecommerce store is now LIVE 🚀",
                                'offers'  => "✅ Complete product catalog\n✅ Integrated payments & order tracking\n✅ Inventory management\n✅ Optimized for selling online",
                                'benefit' => "Start selling your products globally and automate your sales process today.",
                                'cta'     => "Start selling online! Activate for ₹{$price}.",
                            ],
                        ];

                        $tpl = $templates[$tier] ?? $templates['small'];

                        $message = "Hi {$record->business_name} 👋\n\n" .
                                   "{$tpl['intro']}\n\n" .
                                   "🌐 View your site:\n" .
                                   "{$visitUrl}\n\n" .
                                   "{$tpl['offers']}\n\n" .
                                   "⚡ Right now, this is a FREE demo version\n\n" .
                                   "👉 {$tpl['cta']}\n" .
                                   "{$tpl['benefit']}\n\n" .
                                   "GPAY: 9629759769 and share the screenshot, your site will go live permanently.\n\n" .
                                   "Let me know if you want any changes 👍";

                        $waMessage = rawurlencode($message);
                        $phone = preg_replace('/[^0-9]/', '', $record->phone);
                        
                        return redirect()->away("https://wa.me/{$phone}?text={$waMessage}");
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
