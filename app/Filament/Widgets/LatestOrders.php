<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrders extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->is_admin === true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('آخر الطلبات')
            ->query(Order::query())
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('order_number')
                    ->label('رقم الطلب'),

                TextColumn::make('customer_name')
                    ->label('العميل'),

                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->numeric(decimalPlaces: 2),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime(),
            ])
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record]));
    }
}
