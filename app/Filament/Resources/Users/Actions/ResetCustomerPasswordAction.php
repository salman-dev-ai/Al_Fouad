<?php

namespace App\Filament\Resources\Users\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetCustomerPasswordAction
{
    // إجراء يعيّن كلمة مرور جديدة لعميل نسي كلمته (يقترح كلمة عشوائية يمكن تعديلها).
    // بعد الحفظ يُنهي كل جلسات العميل المفتوحة (إن كانت الجلسات في قاعدة البيانات) ويبدّل رمز التذكّر.
    // يرسل المدير الكلمة للعميل عبر واتساب، وتُشفَّر قبل التخزين (cast hashed في User).
    public static function make(): Action
    {
        return Action::make('resetPassword')
            ->label('إعادة ضبط كلمة المرور')
            ->icon('heroicon-m-key')
            ->color('warning')
            ->schema([
                TextInput::make('password')
                    ->label('كلمة المرور الجديدة')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->maxLength(64)
                    ->default(fn (): string => Str::random(12))
                    ->helperText('انسخها وأرسلها للعميل عبر واتساب، وانصحه بتغييرها بعد الدخول.'),
            ])
            ->action(function (array $data, User $record): void {
                $record->forceFill([
                    'password' => $data['password'],
                    'remember_token' => Str::random(60),
                ])->save();

                if (config('session.driver') === 'database') {
                    DB::table('sessions')->where('user_id', $record->id)->delete();
                }

                Notification::make()
                    ->title('تم تعيين كلمة المرور الجديدة')
                    ->success()
                    ->send();
            });
    }
}