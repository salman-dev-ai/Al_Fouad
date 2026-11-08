<?php

namespace App\Filament\Resources\Partners;

use App\Filament\Resources\Partners\Pages\ManagePartners;
use App\Models\Partner;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

// إدارة شركاء المحل (الشركات التي يتعامل معها): اسم وشعار ورابط اختياري، مع ترتيب بالسحب وتفعيل/إخفاء.
// تظهر شعاراتهم في شريط دائري متحرك بصفحة «قصتنا».
// الشعار يُصغَّر عند الرفع (400×400 بنسبة محفوظة) ليكون خفيفًا.
class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-building-2';

    protected static string|UnitEnum|null $navigationGroup = 'صفحة «قصتنا»';

    protected static ?string $navigationLabel = 'الشركاء';

    protected static ?string $modelLabel = 'شريك';

    protected static ?string $pluralModelLabel = 'الشركاء';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('اسم الشريك')->required()->maxLength(100),
            FileUpload::make('logo')
                ->label('الشعار')
                ->image()
                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                ->disk('public')
                ->directory('partners')
                ->visibility('public')
                ->maxSize(1024)
                ->imageResizeMode('contain')
                ->imageResizeTargetWidth('400')
                ->imageResizeTargetHeight('400')
                ->required()
                ->helperText('PNG أو WebP بخلفية شفافة أو بيضاء، ويفضّل شعار مربع.'),
            TextInput::make('url')
                ->label('رابط الشريك (اختياري)')
                ->url()
                ->maxLength(2048)
                ->rules(['starts_with:https://'])
                ->validationMessages(['starts_with' => 'يجب أن يبدأ الرابط بـ https://']),
            Toggle::make('is_active')->label('ظاهر في الصفحة')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('logo')->label('الشعار')->disk('public')->circular(),
                TextColumn::make('name')->label('الاسم')->searchable(),
                ToggleColumn::make('is_active')->label('ظاهر'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Partner::query()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getPages(): array
    {
        return ['index' => ManagePartners::route('/')];
    }
}
