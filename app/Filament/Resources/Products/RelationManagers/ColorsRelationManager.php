<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductVariant;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

class ColorsRelationManager extends RelationManager
{
    protected static string $relationship = 'colors';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'الألوان والصور والمقاسات';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('اللون')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('اسم اللون')
                        ->required()
                        ->maxLength(50)
                        ->unique(
                            table: 'product_colors',
                            column: 'name',
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('product_id', $this->getOwnerRecord()->getKey()),
                        )
                        ->validationMessages(['unique' => 'هذا اللون مضاف لهذا المنتج مسبقًا.']),

                    ColorPicker::make('hex')
                        ->label('درجة اللون')
                        ->required()
                        ->regex('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/')
                        ->helperText('اختر اللون الحقيقي، يظهر للعميل كدائرة ملونة.'),
                ]),

            Section::make('صور هذا اللون')
                ->description('تظهر للعميل عند اختيار هذا اللون فقط. رتّبها بالسحب، وحدّد رئيسية واحدة.')
                ->schema([
                    Repeater::make('images')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('sort_order')
                        ->reorderable()
                        ->collapsible()
                        ->addActionLabel('إضافة صورة')
                        ->maxItems(10)
                        ->columns(3)
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
                                ->label('وصف الصورة (اختياري)')
                                ->maxLength(255),

                            Toggle::make('is_primary')
                                ->label('الصورة الرئيسية')
                                ->distinct()
                                ->fixIndistinctState(),
                        ]),
                ]),

            Section::make('المقاسات والمخزون')
                ->description('سعر ومخزون كل مقاس من هذا اللون.')
                ->schema([
                    Repeater::make('variants')
                        ->hiddenLabel()
                        ->relationship()
                        ->minItems(1)
                        ->defaultItems(1)
                        ->collapsible()
                        ->cloneable()
                        ->addActionLabel('إضافة مقاس')
                        ->columns(5)
                        ->itemLabel(fn (array $state): ?string => $state['size'] ?? null)
                        ->rules([
                            fn (): Closure => function (string $attribute, $value, Closure $fail): void {
                                $sizes = collect($value)->pluck('size')->filter();

                                if ($sizes->count() !== $sizes->unique()->count()) {
                                    $fail('لا يمكن تكرار المقاس نفسه داخل اللون الواحد.');
                                }
                            },
                        ])
                        ->schema([
                            Select::make('size')
                                ->label('المقاس')
                                ->options(array_combine(ProductVariant::SIZES, ProductVariant::SIZES))
                                ->required()
                                ->live(onBlur: true),

                            TextInput::make('price')
                                ->label('السعر')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->suffix('ريال'),

                            TextInput::make('compare_at_price')
                                ->label('السعر قبل الخصم')
                                ->numeric()
                                ->minValue(0)
                                ->gt('price')
                                ->suffix('ريال'),

                            TextInput::make('stock_quantity')
                                ->label('المخزون')
                                ->numeric()
                                ->integer()
                                ->minValue(0)
                                ->default(0)
                                ->required(),

                            TextInput::make('sku')
                                ->label('رمز المنتج (SKU)')
                                ->maxLength(64)
                                ->unique(table: 'product_variants', column: 'sku', ignoreRecord: true),
                        ]),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ColorColumn::make('hex')->label('الدرجة'),

                TextColumn::make('name')->label('اللون')->weight('bold'),

                TextColumn::make('images_count')->label('الصور')->counts('images'),

                TextColumn::make('variants_count')->label('المقاسات')->counts('variants'),

                TextColumn::make('variants_sum_stock_quantity')
                    ->label('إجمالي المخزون')
                    ->sum('variants', 'stock_quantity')
                    ->default(0),
            ])
            ->headerActions([
                CreateAction::make()->label('إضافة لون')->modalWidth(Width::FiveExtraLarge),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل')->modalWidth(Width::FiveExtraLarge),
                DeleteAction::make()
                    ->label('حذف')
                    ->modalDescription('سيُحذف اللون مع مقاساته وصوره. الطلبات السابقة تبقى كما هي.'),
            ])
            ->emptyStateHeading('لا توجد ألوان بعد')
            ->emptyStateDescription('أضف لونًا ثم صوره ومقاساته ليظهر المنتج في المتجر.');
    }
}
