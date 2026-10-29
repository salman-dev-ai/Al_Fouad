<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\Actions\ResetCustomerPasswordAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    // جدول حسابات العملاء: الاسم والجوال والبريد وتاريخ الإنشاء، مع بحث بالاسم والجوال والبريد.
    // الإجراء الوحيد في كل صف هو إعادة ضبط كلمة المرور.
    // الأحدث أولًا.
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                TextColumn::make('phone')->label('الجوال')->searchable()->copyable(),
                TextColumn::make('email')->label('البريد')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->label('تاريخ التسجيل')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ResetCustomerPasswordAction::make(),
            ])
            ->emptyStateHeading('لا توجد حسابات عملاء بعد');
    }
}