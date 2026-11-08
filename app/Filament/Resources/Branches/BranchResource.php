<?php

namespace App\Filament\Resources\Branches;

use App\Filament\Resources\Branches\Pages\ManageBranches;
use App\Models\Branch;
use App\Support\GoogleMapsUrl;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

// إدارة فروع المحل: الاسم والمدينة والعنوان والهاتف والواتساب ورابط الخريطة وأوقات العمل وصورة اختيارية.
// رابط الخريطة يُقبل من Google فقط عبر GoogleMapsUrl (نفس حماية صفحة الإتمام)، والأرقام تُتحقق بصيغة صارمة.
// تظهر في قسم «فروعنا» بصفحة قصتنا بالترتيب المسحوب.
class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-map-pin';

    protected static string|UnitEnum|null $navigationGroup = 'صفحة «قصتنا»';

    protected static ?string $navigationLabel = 'الفروع';

    protected static ?string $modelLabel = 'فرع';

    protected static ?string $pluralModelLabel = 'الفروع';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('اسم الفرع')->required()->maxLength(100),
            TextInput::make('city')->label('المدينة')->maxLength(100),
            Textarea::make('address')->label('العنوان')->rows(2)->required()->maxLength(500)->columnSpanFull(),
            self::phoneField('phone', 'هاتف الفرع'),
            self::phoneField('whatsapp_phone', 'واتساب الفرع')->helperText('بالصيغة الدولية، مثل +967777123456.'),
            TextInput::make('map_url')
                ->label('رابط الموقع على خرائط Google')
                ->url()
                ->maxLength(2048)
                ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (filled($value) && ! GoogleMapsUrl::isValid($value)) {
                        $fail('يجب أن يكون رابط خرائط Google صحيحًا يبدأ بـ https://.');
                    }
                })
                ->columnSpanFull(),
            TextInput::make('hours_text')->label('أوقات العمل')->maxLength(255)->helperText('مثل: يوميًا من 9 صباحًا حتى 10 مساءً، والجمعة مغلق.')->columnSpanFull(),
            FileUpload::make('image')
                ->label('صورة الفرع (اختياري)')
                ->image()
                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                ->disk('public')
                ->directory('branches')
                ->visibility('public')
                ->maxSize(2048)
                ->imageResizeMode('cover')
                ->imageCropAspectRatio('16:9')
                ->imageResizeTargetWidth('960')
                ->imageResizeTargetHeight('540')
                ->columnSpanFull(),
            Toggle::make('is_active')->label('ظاهر في الصفحة')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image')->label('الصورة')->disk('public'),
                TextColumn::make('name')->label('الفرع')->searchable(),
                TextColumn::make('city')->label('المدينة'),
                TextColumn::make('phone')->label('الهاتف'),
                ToggleColumn::make('is_active')->label('ظاهر'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Branch::query()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getPages(): array
    {
        return ['index' => ManageBranches::route('/')];
    }

    // حقل هاتف بصيغة صارمة (أرقام، ويمكن أن يبدأ بـ +)، نفس قاعدة صفحة إعدادات المحل.
    // يمنع إدخال نص عشوائي يُبنى منه لاحقًا رابط اتصال أو واتساب.
    // يُستعمل لهاتف الفرع وواتسابه.
    private static function phoneField(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->tel()
            ->maxLength(30)
            ->regex('/^\+?[0-9][0-9\s\-]{5,19}$/')
            ->validationMessages(['regex' => 'أدخل رقمًا صحيحًا (أرقام فقط، ويمكن أن يبدأ بـ +).']);
    }
}
