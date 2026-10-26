<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الطلب')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('order_number')
                            ->label('رقم الطلب')
                            ->copyable(),
                        TextEntry::make('status')
                            ->label('الحالة')
                            ->badge(),
                        TextEntry::make('created_at')
                            ->label('تاريخ الطلب')
                            ->dateTime(),
                    ]),

                Section::make('العميل والتوصيل')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('customer_name')->label('الاسم'),
                        TextEntry::make('customer_phone')->label('الهاتف')->copyable(),
                        TextEntry::make('city')->label('المدينة')->placeholder('—'),
                        TextEntry::make('delivery_address')->label('عنوان التوصيل'),
                        TextEntry::make('notes')->label('ملاحظات')->placeholder('لا توجد')->columnSpanFull(),
                    ]),

                Section::make('المنتجات')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('المنتج'),
                                TableColumn::make('المقاس'),
                                TableColumn::make('اللون'),
                                TableColumn::make('سعر القطعة'),
                                TableColumn::make('الكمية'),
                                TableColumn::make('الإجمالي'),
                            ])
                            ->schema([
                                TextEntry::make('product_name'),
                                TextEntry::make('size'),
                                TextEntry::make('color'),
                                TextEntry::make('unit_price')->numeric(decimalPlaces: 2),
                                TextEntry::make('quantity'),
                                TextEntry::make('line_total')->numeric(decimalPlaces: 2),
                            ]),
                    ]),

                Section::make('المبالغ')
                    ->columns(5)
                    ->schema([
                        TextEntry::make('subtotal')->label('المجموع الفرعي')->numeric(decimalPlaces: 2),
                        TextEntry::make('tax_rate')->label('نسبة الضريبة %')->numeric(decimalPlaces: 2),
                        TextEntry::make('tax_amount')->label('الضريبة')->numeric(decimalPlaces: 2),
                        TextEntry::make('delivery_fee')->label('رسوم التوصيل')->numeric(decimalPlaces: 2),
                        TextEntry::make('total')->label('الإجمالي')->numeric(decimalPlaces: 2)->weight('bold'),
                    ]),
            ]);
    }
}
