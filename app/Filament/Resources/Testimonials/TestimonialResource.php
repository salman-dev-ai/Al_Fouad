<?php

namespace App\Filament\Resources\Testimonials;

use App\Filament\Resources\Testimonials\Pages\ManageTestimonials;
use App\Models\Testimonial;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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

// إدارة آراء العملاء المنتقاة التي تظهر في شريط «ماذا يقول عملاؤنا»: الاسم والوصف والنص والتقييم وصورة اختيارية.
// ترتيب بالسحب وإخفاء بدون حذف، والنص محدود بـ 500 حرف ليبقى الشريط أنيقًا.
// منفصلة عن تقييمات المنتجات المرتبطة بالشراء.
class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static string|UnitEnum|null $navigationGroup = 'صفحة «قصتنا»';

    protected static ?string $navigationLabel = 'آراء العملاء';

    protected static ?string $modelLabel = 'رأي';

    protected static ?string $pluralModelLabel = 'آراء العملاء';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('اسم العميل')->required()->maxLength(100),
            TextInput::make('subtitle')->label('وصف قصير (اختياري)')->maxLength(100)->helperText('مثل: عميلة من صنعاء'),
            Textarea::make('quote')->label('الرأي')->rows(4)->required()->maxLength(500),
            Select::make('rating')
                ->label('التقييم')
                ->options([5 => '5 نجوم', 4 => '4 نجوم', 3 => '3 نجوم', 2 => 'نجمتان', 1 => 'نجمة'])
                ->default(5)
                ->required(),
            FileUpload::make('avatar')
                ->label('صورة العميل (اختياري)')
                ->image()
                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                ->disk('public')
                ->directory('testimonials')
                ->visibility('public')
                ->maxSize(1024)
                ->imageResizeMode('cover')
                ->imageCropAspectRatio('1:1')
                ->imageResizeTargetWidth('200')
                ->imageResizeTargetHeight('200'),
            Toggle::make('is_active')->label('ظاهر في الصفحة')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('avatar')->label('الصورة')->disk('public')->circular(),
                TextColumn::make('name')->label('الاسم')->searchable(),
                TextColumn::make('quote')->label('الرأي')->limit(70),
                TextColumn::make('rating')->label('التقييم')->formatStateUsing(fn ($state): string => $state . ' / 5'),
                ToggleColumn::make('is_active')->label('ظاهر'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTestimonials::route('/')];
    }
}
