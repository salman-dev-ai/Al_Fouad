<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

// مورد عرض حسابات العملاء فقط (is_admin = false) داخل مجموعة «المبيعات».
// لا إنشاء ولا تعديل ولا حذف من هنا، حماية لحسابات الإدارة ولبيانات العملاء.
// الإجراء المتاح هو إعادة ضبط كلمة المرور (ResetCustomerPasswordAction).
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $modelLabel = 'حساب عميل';

    protected static ?string $pluralModelLabel = 'حسابات العملاء';

    protected static ?string $navigationLabel = 'حسابات العملاء';

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    // يمنع ظهور المديرين في هذه القائمة مهما كان الرابط.
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_admin', false);
    }

    // لا زر «إنشاء»: الحسابات تُنشأ من المتجر فقط.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }
}