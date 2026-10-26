<?php

namespace App\Filament\Pages;

use App\Models\StoreSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageStoreSettings extends Page
{
    protected string $view = 'filament.pages.manage-store-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?string $navigationLabel = 'إعدادات المحل';

    protected static ?string $title = 'إعدادات المحل';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** الصفحات المخصصة لا تطبّق السياسات تلقائيًا، فنقفلها على المدراء صراحةً. */
    public static function canAccess(): bool
    {
        return auth()->user()?->is_admin === true;
    }

    public function mount(): void
    {
        $data = array_merge([
            'currency_code' => 'YER',
            'delivery_fee' => 0,
            'tax_enabled' => false,
            'tax_rate' => 0,
            'announcement_enabled' => false,
            'maintenance_mode' => false,
        ], $this->getRecord()?->attributesToArray() ?? []);

        $data['opening_hours'] = $data['opening_hours'] ?? self::defaultHours();


        $data['social_links'] = $data['social_links'] ?? [];

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('التواصل والموقع')
                        ->columns(2)
                        ->schema([
                            self::phoneField('phone', 'رقم هاتف المحل'),
                            self::phoneField('whatsapp_phone', 'رقم واتساب')
                                ->helperText('بالصيغة الدولية، مثل +967777123456.'),
                            TextInput::make('email')
                                ->label('البريد الإلكتروني')
                                ->email()
                                ->maxLength(255),
                            TextInput::make('map_url')
                                ->label('رابط الموقع على خرائط Google')
                                ->url()
                                ->maxLength(2048)
                                ->rules(['starts_with:https://'])
                                ->validationMessages(['starts_with' => 'يجب أن يبدأ الرابط بـ https://'])
                                ->helperText('اختياري.'),
                            Textarea::make('address')
                                ->label('عنوان المحل')
                                ->rows(2)
                                ->maxLength(500)
                                ->columnSpanFull(),
                        ]),

                    Section::make('التوصيل والضريبة والعملة')
                        ->columns(2)
                        ->schema([
                            TextInput::make('delivery_fee')
                                ->label('رسوم التوصيل الافتراضية')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->suffix('ريال'),
                            Select::make('currency_code')
                                ->label('العملة')
                                ->options([
                                    'YER' => 'ريال يمني (YER)',
                                    'SAR' => 'ريال سعودي (SAR)',
                                    'USD' => 'دولار أمريكي (USD)',
                                ])
                                ->required()
                                ->helperText('تنبيه: تغييرها بعد وجود طلبات لا يحوّل مبالغها القديمة.'),
                            Toggle::make('tax_enabled')
                                ->label('تفعيل الضريبة')
                                ->helperText('الأسعار المعروضة لا تشمل الضريبة، وتُضاف عند الطلب.')
                                ->live(),
                            TextInput::make('tax_rate')
                                ->label('نسبة الضريبة %')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->visible(fn ($get): bool => (bool) $get('tax_enabled'))
                                ->required(fn ($get): bool => (bool) $get('tax_enabled')),
                        ]),

                    Section::make('أوقات العمل')
                        ->collapsible()
                        ->schema([
                            Repeater::make('opening_hours')
                                ->hiddenLabel()
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->columns(4)
                                ->schema([
                                    TextInput::make('day')
                                        ->label('اليوم')
                                        ->readOnly(),
                                    Toggle::make('is_closed')
                                        ->label('مغلق')
                                        ->live(),
                                    TimePicker::make('opens_at')
                                        ->label('يفتح')
                                        ->seconds(false)
                                        ->hidden(fn ($get): bool => (bool) $get('is_closed'))
                                        ->required(fn ($get): bool => ! $get('is_closed')),
                                    TimePicker::make('closes_at')
                                        ->label('يغلق')
                                        ->seconds(false)
                                        ->hidden(fn ($get): bool => (bool) $get('is_closed'))
                                        ->required(fn ($get): bool => ! $get('is_closed')),
                                ]),
                        ]),

                    Section::make('روابط التواصل الاجتماعي')
                        ->collapsible()
                        ->columns(2)
                        ->schema([
                            self::socialField('facebook', 'فيسبوك'),
                            self::socialField('instagram', 'إنستغرام'),
                            self::socialField('tiktok', 'تيك توك'),
                            self::socialField('x', 'إكس (تويتر)'),
                            self::socialField('snapchat', 'سناب شات'),
                            self::socialField('telegram', 'تيليجرام'),
                        ]),

                    Section::make('الإعلان النصي وحالة المتجر')
                        ->columns(2)
                        ->schema([
                            Toggle::make('announcement_enabled')
                                ->label('إظهار الإعلان النصي')
                                ->live(),
                            TextInput::make('announcement_text')
                                ->label('نص الإعلان')
                                ->maxLength(255)
                                ->required(fn ($get): bool => (bool) $get('announcement_enabled')),
                            Toggle::make('maintenance_mode')
                                ->label('وضع الصيانة (إيقاف المتجر مؤقتًا)')
                                ->helperText('يغلق المتجر أمام الزبائن، وتبقى لوحة الإدارة متاحة.')
                                ->columnSpanFull(),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('حفظ الإعدادات')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        $data['phone'] = self::normalizePhone($data['phone'] ?? null);
        $data['whatsapp_phone'] = self::normalizePhone($data['whatsapp_phone'] ?? null);

        if (! ($data['tax_enabled'] ?? false)) {
            $data['tax_rate'] = 0;
        }

        $data['opening_hours'] = array_values($data['opening_hours'] ?? []);
        $data['social_links'] = array_filter($data['social_links'] ?? [], fn ($url): bool => filled($url));

        $record = $this->getRecord() ?? new StoreSetting();
        $record->fill($data);
        $record->save();

        Notification::make()
            ->success()
            ->title('تم حفظ الإعدادات')
            ->send();
    }

    public function getRecord(): ?StoreSetting
    {
        return StoreSetting::query()->first();
    }

    private static function phoneField(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->tel()
            ->maxLength(30)
            ->regex('/^\+?[0-9][0-9\s\-]{5,19}$/')
            ->validationMessages(['regex' => 'أدخل رقمًا صحيحًا (أرقام فقط، ويمكن أن يبدأ بـ +).']);
    }

    private static function socialField(string $key, string $label): TextInput
    {
        return TextInput::make("social_links.{$key}")
            ->label($label)
            ->url()
            ->maxLength(2048)
            ->rules(['starts_with:https://'])
            ->validationMessages(['starts_with' => 'يجب أن يبدأ الرابط بـ https://']);
    }

    private static function normalizePhone(?string $phone): ?string
    {
        $clean = preg_replace('/[\s\-]/', '', (string) $phone);

        return $clean === '' ? null : $clean;
    }

    /** @return array<int, array<string, mixed>> */
    private static function defaultHours(): array
    {
        return collect(['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'])
            ->map(fn (string $day): array => [
                'day' => $day,
                'is_closed' => $day === 'الجمعة',
                'opens_at' => '09:00',
                'closes_at' => '22:00',
            ])
            ->all();
    }
}
