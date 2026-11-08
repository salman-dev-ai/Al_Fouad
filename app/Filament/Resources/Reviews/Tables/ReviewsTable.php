<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Enums\ReviewStatus;
use App\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

// جدول مراجعة التقييمات: المعلّقة تظهر افتراضيًا ليعتمدها المدير أو يرفضها.
// الاعتماد والرفض يضبطان الحالة صراحة (forceFill) لأنها ليست قابلة للتعبئة الجماعية، ولا علاقة لهما بدرجة النجوم.
// المراقب يعيد حساب متوسط المنتج تلقائيًا بعد كل تغيير، وزر «الصور» يعرض صور التقييم للمراجعة.
class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['product:id,name', 'user:id,name', 'images']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('product.name')->label('المنتج')->searchable(),
                TextColumn::make('user.name')->label('العميل')->searchable(),
                TextColumn::make('rating')->label('التقييم')->formatStateUsing(fn ($state): string => $state . ' / 5')->sortable(),
                TextColumn::make('body')->label('النص')->limit(300)->wrap(),
                TextColumn::make('tags')->label('الوسوم')->badge()->state(fn (Review $record): array => $record->tagLabels()),
                TextColumn::make('size_data')->label('المقاس')->state(fn (Review $record): ?string => $record->sizeSummary())->wrap(),
                TextColumn::make('images_count')->label('الصور')->counts('images'),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('created_at')->label('التاريخ')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(collect(ReviewStatus::cases())->mapWithKeys(fn (ReviewStatus $s): array => [$s->value => $s->getLabel()])->all())
                    ->default(ReviewStatus::Pending->value),
            ])
            ->recordActions([
                Action::make('photos')
                    ->label('الصور')
                    ->icon('heroicon-o-photo')
                    ->visible(fn (Review $record): bool => $record->images->isNotEmpty())
                    ->modalHeading('صور التقييم')
                    ->modalContent(fn (Review $record) => view('filament.review-photos', ['review' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق'),
                Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Review $record): bool => $record->status !== ReviewStatus::Approved)
                    ->requiresConfirmation()
                    ->action(fn (Review $record) => $record->forceFill(['status' => ReviewStatus::Approved, 'reviewed_at' => now()])->save()),
                Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Review $record): bool => $record->status !== ReviewStatus::Rejected)
                    ->requiresConfirmation()
                    ->action(fn (Review $record) => $record->forceFill(['status' => ReviewStatus::Rejected, 'reviewed_at' => now()])->save()),
                DeleteAction::make(),
            ]);
    }
}
