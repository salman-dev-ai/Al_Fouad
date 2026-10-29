<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\ProductVariant;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Closure;
use App\Support\ColorHex;
use Filament\Forms\Components\ColorPicker;

class ProductForm
{
    public const SIZES = ProductVariant::SIZES;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات المنتج')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم المنتج')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $set, ?string $state): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state, language: null));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('الرابط (slug)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->regex('/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u')
                            ->validationMessages([
                                'regex' => 'يُسمح بالحروف والأرقام والشرطة (-) فقط، بدون مسافات.',
                                'unique' => 'هذا الرابط مستخدم لمنتج آخر.',
                            ]),

                        Select::make('category_id')
                            ->label('التصنيف')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Textarea::make('description')
                            ->label('الوصف')
                            ->maxLength(3000)
                            ->rows(4)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('معروض في المتجر')
                            ->helperText('أوقفه لإخفاء المنتج (أرشفة) بدل حذفه.')
                            ->default(true),

                        Toggle::make('is_featured')
                            ->label('منتج مميز')
                            ->default(false),
                    ]),

                Section::make('الصور العامة (للمنتجات القديمة)')
                    ->description('حدّد صورة رئيسية واحدة، ورتّب الصور بالسحب.')
                    ->schema([
                        Repeater::make('generalImages')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->reorderable()
                            ->collapsible()
                            ->addActionLabel('إضافة صورة')
                            ->maxItems(8)
                            ->columns(3)
                            ->collapsed()
                            ->schema([
                                FileUpload::make('image_path')
                                    ->label('الصورة')
                                    ->image()
                                    ->disk('public')
                                    ->directory('products')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(2048)
                                    ->required(),

                                TextInput::make('alt_text')
                                    ->label('وصف الصورة (للقارئات وSEO)')
                                    ->maxLength(255),

                                Toggle::make('is_primary')
                                    ->label('الصورة الرئيسية')
                                    ->distinct()
                                    ->default(false),
                            ]),
                    ]),

            ]);
    }
}
