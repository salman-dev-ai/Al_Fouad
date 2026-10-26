<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم التصنيف')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $set, ?string $state): void {
                        // نولّد الرابط تلقائيًا عند الإضافة فقط، حتى لا تنكسر الروابط القديمة عند التعديل
                        if ($operation === 'create') {
                            $set('slug', Str::slug((string) $state, language: null));
                        }
                    }),

                TextInput::make('slug')
                    ->label('الرابط (slug)')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->regex('/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u')
                    ->validationMessages([
                        'regex' => 'يُسمح بالحروف والأرقام والشرطة (-) فقط، بدون مسافات.',
                        'unique' => 'هذا الرابط مستخدم لتصنيف آخر.',
                    ])
                    ->helperText('يظهر في رابط الصفحة، ويمكنك تعديله يدويًا (مثل shirts).'),

                Textarea::make('description')
                    ->label('الوصف')
                    ->maxLength(1000)
                    ->columnSpanFull(),

                FileUpload::make('image')
                    ->label('صورة التصنيف')
                    ->image()
                    ->disk('public')
                    ->directory('categories')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->helperText('JPG أو PNG أو WebP، بحد أقصى 2 ميجابايت.'),

                Toggle::make('is_active')
                    ->label('مفعّل')
                    ->default(true),
            ]);
    }
}
