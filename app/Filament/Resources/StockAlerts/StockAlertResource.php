<?php

namespace App\Filament\Resources\StockAlerts;

use App\Filament\Resources\StockAlerts\Pages\CreateStockAlert;
use App\Filament\Resources\StockAlerts\Pages\EditStockAlert;
use App\Filament\Resources\StockAlerts\Pages\ListStockAlerts;
use App\Filament\Resources\StockAlerts\Schemas\StockAlertForm;
use App\Filament\Resources\StockAlerts\Tables\StockAlertsTable;
use App\Models\StockAlert;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockAlertResource extends Resource
{
    protected static ?string $model = StockAlert::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return StockAlertForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockAlertsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }


        // التنبيهات تُنشأ من المتجر فقط، فلا إنشاء ولا تعديل من الإدارة (عرض + إرسال + حذف).
    // نفس أسلوب مورد العملاء عندنا.
    // رابط إنشاء/تعديل يعيد 403.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function getModelLabel(): string
    {
        return 'تنبيه توفر';
    }

    public static function getPluralModelLabel(): string
    {
        return 'تنبيهات التوفر';
    }

    

    public static function getPages(): array
    {
        return [
            'index' => ListStockAlerts::route('/'),
            'create' => CreateStockAlert::route('/create'),
            'edit' => EditStockAlert::route('/{record}/edit'),
        ];
    }
}
