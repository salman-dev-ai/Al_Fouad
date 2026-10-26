<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('primaryImage.image_path')
                    ->label('الصورة')
                    ->disk('public'),

                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->sortable(),

                TextColumn::make('variants_count')
                    ->label('الخيارات')
                    ->counts('variants'),

                TextColumn::make('variants_min_price')
                    ->label('أقل سعر')
                    ->min('variants', 'price')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('variants_sum_stock_quantity')
                    ->label('المخزون الكلي')
                    ->sum('variants', 'stock_quantity')
                    ->sortable(),

                IconColumn::make('is_featured')
                    ->label('مميز')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('معروض')
                    ->boolean(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('التصنيف')
                    ->relationship('category', 'name'),

                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('المعروضة')
                    ->falseLabel('المؤرشفة'),

                TernaryFilter::make('is_featured')
                    ->label('المميزة')
                    ->placeholder('الكل')
                    ->trueLabel('المميزة فقط')
                    ->falseLabel('غير المميزة'),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('عرض في المتجر')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('archive')
                        ->label('أرشفة (إخفاء)')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
