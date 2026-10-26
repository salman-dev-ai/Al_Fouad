<?php

namespace App\Filament\Resources\Advertisements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdvertisementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('محتوى الإعلان')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('العنوان')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('link_url')
                            ->label('الرابط عند الضغط')
                            ->maxLength(2048)
                            ->regex('/^(https?:\/\/|\/)\S+$/')
                            ->validationMessages([
                                'regex' => 'يجب أن يبدأ الرابط بـ https:// أو http:// أو / (مثل /products).',
                            ])
                            ->helperText('اتركه فارغًا إن لم يكن للإعلان رابط.'),

                        Textarea::make('description')
                            ->label('نص الإعلان')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),

                        FileUpload::make('image_path')
                            ->label('صورة الإعلان')
                            ->image()
                            ->disk('public')
                            ->directory('advertisements')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(3072)
                            ->helperText('يُفضّل صورة عريضة (مثل 1600×600)، بحد أقصى 3 ميجابايت.')
                            ->columnSpanFull(),
                    ]),

                Section::make('العرض والجدولة')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('start_at')
                            ->label('بداية العرض')
                            ->helperText('اتركه فارغًا ليبدأ فورًا.'),

                        DateTimePicker::make('end_at')
                            ->label('نهاية العرض')
                            ->after('start_at')
                            ->validationMessages(['after' => 'يجب أن تكون النهاية بعد البداية.'])
                            ->helperText('اتركه فارغًا ليستمر دون نهاية.'),

                        TextInput::make('sort_order')
                            ->label('الترتيب')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('الأصغر يظهر أولًا في البانر.'),

                        Toggle::make('is_active')
                            ->label('مفعّل')
                            ->default(true),
                    ]),
            ]);
    }
}
