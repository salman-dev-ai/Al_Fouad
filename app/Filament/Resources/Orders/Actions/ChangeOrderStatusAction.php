<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Actions\Orders\ChangeOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class ChangeOrderStatusAction
{
    public static function make(): Action
    {
        return Action::make('changeStatus')
            ->label('تغيير الحالة')
            ->icon('heroicon-m-arrow-path')
            ->color('primary')
            ->visible(fn (Order $record): bool => $record->status->allowedTransitions() !== [])
            ->schema([
                Select::make('status')
                    ->label('الحالة الجديدة')
                    ->options(fn (Order $record): array => collect($record->status->allowedTransitions())
                        ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => $status->getLabel()])
                        ->all())
                    ->required()
                    ->helperText('إلغاء الطلب يعيد كمياته إلى المخزون، ولا يمكن التراجع عنه.'),
            ])
            ->action(function (array $data, Order $record): void {
                try {
                    app(ChangeOrderStatus::class)->execute($record, OrderStatus::from($data['status']));
                } catch (DomainException $e) {
                    Notification::make()
                        ->title('تعذّر تغيير الحالة')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $record->refresh();

                Notification::make()
                    ->title('تم تغيير حالة الطلب')
                    ->success()
                    ->send();
            });
    }
}
