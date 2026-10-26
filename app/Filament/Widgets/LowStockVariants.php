<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductVariant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockVariants extends BaseWidget
{
    /** الحد الذي نعدّ عنده المخزون منخفضًا. */
    private const THRESHOLD = 5;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->is_admin === true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('مخزون منخفض (' . self::THRESHOLD . ' قطع أو أقل)')
            ->query(
                ProductVariant::query()
                    ->with('product')
                    ->where('stock_quantity', '<=', self::THRESHOLD)
            )
            ->defaultSort('stock_quantity', 'asc')
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('product.name')
                    ->label('المنتج'),

                TextColumn::make('size')
                    ->label('المقاس'),

                TextColumn::make('color')
                    ->label('اللون'),

                TextColumn::make('stock_quantity')
                    ->label('المتبقي')
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : 'warning'),
            ])
            ->recordUrl(fn (ProductVariant $record): string => ProductResource::getUrl('edit', ['record' => $record->product_id]));
    }
}
