<?php

namespace App\Filament\Resources\Advertisements\Tables;

use App\Models\Advertisement;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdvertisementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('الصورة')
                    ->imageSize(60)
                     ->circular()

                    ->disk('public'),

                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable(),

                TextColumn::make('display_status')
                    ->label('حالة العرض')
                    ->badge()
                    ->state(function (Advertisement $record): string {
                        if (! $record->is_active) {
                            return 'متوقف';
                        }
                        if ($record->start_at?->isFuture()) {
                            return 'مجدول';
                        }
                        if ($record->end_at?->isPast()) {
                            return 'منتهي';
                        }

                        return 'يُعرض الآن';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'يُعرض الآن' => 'success',
                        'مجدول' => 'info',
                        'منتهي' => 'gray',
                        default => 'warning',
                    }),

                TextColumn::make('start_at')
                    ->label('البداية')
                    ->dateTime()
                    ->placeholder('فورًا'),

                TextColumn::make('end_at')
                    ->label('النهاية')
                    ->dateTime()
                    ->placeholder('مفتوحة'),

                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}
