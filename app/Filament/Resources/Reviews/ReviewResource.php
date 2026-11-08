<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\CreateReview;
use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Schemas\ReviewForm;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-star';

    protected static string|\UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'التقييمات';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return ReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }


        // التقييمات تأتي من العملاء فقط، فلا إنشاء ولا تعديل نص من الإدارة: اعتماد أو رفض أو حذف.
    // الشارة الصفراء في القائمة تعرض عدد المعلّقة لتنبيه المدير.
    // أي رابط إنشاء أو تعديل يعيد 403.
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
        return 'تقييم';
    }

    public static function getPluralModelLabel(): string
    {
        return 'التقييمات';
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = \App\Models\Review::query()->where('status', \App\Enums\ReviewStatus::Pending->value)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'create' => CreateReview::route('/create'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }
}
