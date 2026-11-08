<?php

namespace App\Filament\Pages;

use App\Support\ReviewSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;

// صفحة إعدادات صور التقييمات: تفعيل الصور، الحد الأقصى لعددها، والحد الأقصى لحجم الصورة.
// القيم تُحفظ في جدول settings عبر ReviewSettings وتُقرأ منه في الواجهة والتحقق، والصور تبقى اختيارية دائمًا.
// الحدود محصورة في 1 إلى 5 للعدد والحجم مهما أُدخل.
class ReviewSettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'إعدادات التقييمات';

    protected static ?string $title = 'إعدادات التقييمات';

    protected static ?string $slug = 'review-settings';

    public ?array $data = [];

    // يملأ النموذج بالقيم الحالية.
    public function mount(): void
    {
        $this->form->fill([
            'photos_enabled' => ReviewSettings::photosEnabled(),
            'max_photos' => ReviewSettings::maxPhotos(),
            'max_photo_mb' => ReviewSettings::maxPhotoMb(),
        ]);
    }

    // حقول النموذج.
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('photos_enabled')->label('تفعيل رفع الصور في التقييمات'),
                TextInput::make('max_photos')->label('الحد الأقصى لعدد الصور')->numeric()->minValue(1)->maxValue(5)->required(),
                TextInput::make('max_photo_mb')->label('الحد الأقصى لحجم الصورة (ميغابايت)')->numeric()->minValue(1)->maxValue(5)->required(),
            ])
            ->statePath('data');
    }

    // محتوى الصفحة: النموذج وزر الحفظ.
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('حفظ')->submit('save')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    // يتحقق ويحفظ الإعدادات ثم يعرض إشعار نجاح.
    public function save(): void
    {
        $state = $this->form->getState();

        ReviewSettings::save(
            (bool) $state['photos_enabled'],
            (int) $state['max_photos'],
            (int) $state['max_photo_mb'],
        );

        Notification::make()->title('تم حفظ الإعدادات')->success()->send();
    }
}
