<?php

namespace App\Filament\Resources\StockAlerts\Tables;

use App\Models\StockAlert;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

// جدول تنبيهات التوفر: الجاهزة للإرسال أولًا (available_at الأحدث)، ثم المعلّقة.
// لكل صف زر واتساب (رسالة جاهزة) وزر «تم الإرسال» الذي يغلق التنبيه.
// تُحمَّل علاقة الخيار والمنتج مسبقًا لتجنب N+1.
class StockAlertsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('variant.product'))
            ->defaultSort('available_at', 'desc')
            ->columns([
                TextColumn::make('variant.product.name')->label('المنتج')->searchable(),
                TextColumn::make('variant.color')->label('اللون'),
                TextColumn::make('variant.size')->label('المقاس'),
                TextColumn::make('variant.stock_quantity')->label('المخزون الحالي'),
                TextColumn::make('phone')->label('الهاتف')->searchable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->state(fn (StockAlert $record): string => match (true) {
                        $record->notified_at !== null => 'تم الإبلاغ',
                        $record->available_at !== null => 'متوفر الآن، أبلغه',
                        default => 'بانتظار التوفر',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'متوفر الآن، أبلغه' => 'success',
                        'تم الإبلاغ' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('consent_at')->label('وقت الموافقة')->dateTime('Y-m-d H:i')->toggleable(),
                TextColumn::make('created_at')->label('تاريخ الطلب')->dateTime('Y-m-d')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('واتساب')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->visible(fn (StockAlert $record): bool => $record->available_at !== null && $record->notified_at === null)
                    ->url(fn (StockAlert $record): string => $record->whatsAppUrl())
                    ->openUrlInNewTab(),
                Action::make('markNotified')
                    ->label('تم الإرسال')
                    ->icon('heroicon-o-check')
                    ->visible(fn (StockAlert $record): bool => $record->available_at !== null && $record->notified_at === null)
                    ->requiresConfirmation()
                    ->action(fn (StockAlert $record) => $record->update(['notified_at' => now()])),
                DeleteAction::make(),
            ]);
    }
}
