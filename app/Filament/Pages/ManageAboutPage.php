<?php

namespace App\Filament\Pages;

use App\Models\AboutPage;
use App\Support\AboutIcons;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
// صفحة إدارة محتوى «قصتنا»: غلاف، قصة، أرقام، مميزات، دفع وتوصيل، استرجاع، أسئلة شائعة، وإظهار الأقسام.
// سجل واحد يُنشأ عند أول حفظ، وبنفس نمط صفحة إعدادات المحل (مدراء فقط، والتحقق مكرر عند الحفظ).
// الشركاء والآراء والفروع لها موارد مستقلة لأنها قوائم كبيرة قابلة للترتيب.
class ManageAboutPage extends Page
{
    protected string $view = 'filament.pages.manage-about-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'صفحة «قصتنا»';

    protected static ?string $navigationLabel = 'محتوى الصفحة';

    protected static ?string $title = 'محتوى صفحة «قصتنا»';

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    // الصفحة مقفلة على المدراء صراحة، لأن الصفحات المخصصة لا تطبّق السياسات تلقائيًا.
    // يُعاد التحقق نفسه داخل save().
    // غير المدير لا يرى الصفحة في القائمة ولا يفتحها.
    public static function canAccess(): bool
    {
        return auth()->user()?->is_admin === true;
    }

    // يملأ النموذج بالمحتوى الحالي أو بقيم ابتدائية مفيدة عند أول استخدام (كل الأقسام ظاهرة، والدفع عند الاستلام).
    // القيم الابتدائية لا تُحفظ إلا عند ضغط «حفظ».
    // يُستدعى تلقائيًا عند فتح الصفحة.
    public function mount(): void
    {
        $data = array_merge([
            'is_published' => true,
            'visible_sections' => array_keys(AboutPage::SECTIONS),
            'payment_methods' => [[
                'name' => 'الدفع عند الاستلام',
                'description' => 'ادفع نقدًا عند استلام طلبك.',
                'icon' => 'banknote',
            ]],
        ], array_filter($this->getRecord()?->attributesToArray() ?? [], fn ($value) => $value !== null));

        $this->form->fill($data);
    }

    // يبني نموذج الصفحة بأقسامه التسعة: الظهور، الغلاف، القصة، الأرقام، المميزات، الدفع والتوصيل، الاسترجاع، الأسئلة، SEO.
    // الصور تُصغَّر في المتصفح قبل الرفع (أسرع وأخف)، والأنواع PNG/JPG/WebP فقط (بلا SVG).
    // النصوص الطويلة Markdown ويُعطَّل HTML الخام عند العرض في الخطوة التالية.
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('الظهور والأقسام')
                        ->description('الشركاء والآراء والفروع تُدار من القائمة الجانبية (نفس المجموعة). أي قسم فارغ لا يظهر تلقائيًا.')
                        ->schema([
                            Toggle::make('is_published')
                                ->label('نشر الصفحة')
                                ->helperText('عند الإيقاف تعيد الصفحة «غير موجودة» للزوار.'),
                            CheckboxList::make('visible_sections')
                                ->label('الأقسام الظاهرة')
                                ->options(AboutPage::SECTIONS)
                                ->columns(2)
                                ->bulkToggleable(),
                        ]),

                    Section::make('الغلاف')
                        ->columns(2)
                        ->schema([
                            TextInput::make('hero_title')->label('العنوان الرئيسي')->maxLength(120),
                            TextInput::make('hero_subtitle')->label('العنوان الفرعي')->maxLength(255),
                            self::imageField('hero_image', 'صورة الغلاف', 'about', 1920, 1080, 2048)
                                ->helperText('يفضّل صورة عريضة (16:9). تُصغَّر تلقائيًا.')
                                ->columnSpanFull(),
                        ]),

                    Section::make('قصتنا ورسالتنا')
                        ->schema([
                            TextInput::make('story_title')->label('عنوان القصة')->maxLength(120),
                            MarkdownEditor::make('story_body')
                                ->label('نص القصة')
                                ->toolbarButtons(['bold', 'italic', 'heading', 'bulletList', 'orderedList', 'link', 'undo', 'redo'])
                                ->rules(['max:8000']),
                            self::imageField('story_image', 'صورة القصة', 'about', 1200, 1200, 1536),
                            Textarea::make('mission')->label('رسالتنا')->rows(3)->maxLength(600),
                            Textarea::make('vision')->label('رؤيتنا')->rows(3)->maxLength(600),
                        ]),

                    Section::make('شريط الأرقام')
                        ->collapsible()
                        ->schema([
                            Repeater::make('stats')
                                ->hiddenLabel()
                                ->maxItems(4)
                                ->defaultItems(0)
                                ->addActionLabel('إضافة رقم')
                                ->itemLabel(fn (array $state): ?string => ($state['value'] ?? '') . ' ' . ($state['label'] ?? ''))
                                ->columns(2)
                                ->schema([
                                    TextInput::make('value')->label('الرقم')->required()->maxLength(20)->helperText('مثل: +5000 أو 12'),
                                    TextInput::make('label')->label('الوصف')->required()->maxLength(40)->helperText('مثل: عميل سعيد'),
                                ]),
                        ]),

                    Section::make('قيمنا ومميزاتنا')
                        ->collapsible()
                        ->schema([
                            Repeater::make('core_values')
                                ->hiddenLabel()
                                ->maxItems(6)
                                ->defaultItems(0)
                                ->addActionLabel('إضافة ميزة')
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                ->columns(2)
                                ->schema([
                                    Select::make('icon')->label('الأيقونة')->options(AboutIcons::OPTIONS)->required(),
                                    TextInput::make('title')->label('العنوان')->required()->maxLength(60),
                                    Textarea::make('text')->label('الوصف')->rows(2)->required()->maxLength(220)->columnSpanFull(),
                                ]),
                        ]),

                    Section::make('الدفع والتوصيل')
                        ->collapsible()
                        ->schema([
                            Repeater::make('payment_methods')
                                ->label('طرق الدفع')
                                ->maxItems(6)
                                ->defaultItems(0)
                                ->addActionLabel('إضافة طريقة دفع')
                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                ->columns(2)
                                ->schema([
                                    Select::make('icon')->label('الأيقونة')->options(AboutIcons::OPTIONS)->required(),
                                    TextInput::make('name')->label('الاسم')->required()->maxLength(60),
                                    Textarea::make('description')->label('الوصف')->rows(2)->maxLength(200)->columnSpanFull(),
                                ]),
                            MarkdownEditor::make('shipping_info')
                                ->label('معلومات التوصيل')
                                ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link', 'undo', 'redo'])
                                ->rules(['max:4000']),
                        ]),

                    Section::make('سياسة الاسترجاع')
                        ->collapsible()
                        ->schema([
                            TextInput::make('return_days')
                                ->label('مدة الاسترجاع (أيام)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(365)
                                ->helperText('تظهر كشارة بارزة، واتركها فارغة إن لم تُرد إبرازها.'),
                            MarkdownEditor::make('return_policy')
                                ->label('نص السياسة')
                                ->toolbarButtons(['bold', 'italic', 'heading', 'bulletList', 'orderedList', 'link', 'undo', 'redo'])
                                ->rules(['max:6000']),
                        ]),

                    Section::make('الأسئلة الشائعة')
                        ->collapsible()
                        ->schema([
                            Repeater::make('faqs')
                                ->hiddenLabel()
                                ->maxItems(12)
                                ->defaultItems(0)
                                ->addActionLabel('إضافة سؤال')
                                ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                ->schema([
                                    TextInput::make('question')->label('السؤال')->required()->maxLength(200),
                                    Textarea::make('answer')->label('الجواب')->rows(3)->required()->maxLength(1000),
                                ]),
                        ]),

                    Section::make('محركات البحث')
                        ->collapsible()
                        ->schema([
                            Textarea::make('seo_description')
                                ->label('وصف الصفحة لمحركات البحث')
                                ->rows(2)
                                ->maxLength(255)
                                ->helperText('جملة أو جملتان، يُفضَّل أقل من 160 حرفًا.'),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('حفظ المحتوى')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    // يحفظ المحتوى بعد التحقق من الصلاحية مرة ثانية، وينظّف القوائم (إعادة ترقيم) ويحصر الأقسام في القائمة البيضاء.
    // السجل يُنشأ عند أول حفظ ثم يُحدَّث، ومسح الكاش يتم تلقائيًا من الموديل.
    // يعرض إشعار نجاح.
    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        $data['visible_sections'] = array_values(array_intersect(
            array_keys(AboutPage::SECTIONS),
            $data['visible_sections'] ?? [],
        ));

        foreach (['stats', 'core_values', 'payment_methods', 'faqs'] as $list) {
            $data[$list] = array_values($data[$list] ?? []);
        }

        $record = $this->getRecord() ?? new AboutPage();
        $record->fill($data);
        $record->save();

        Notification::make()
            ->success()
            ->title('تم حفظ محتوى الصفحة')
            ->send();
    }

    // السجل الوحيد للصفحة أو null قبل أول حفظ.
    // نفس أسلوب ManageStoreSettings.
    public function getRecord(): ?AboutPage
    {
        return AboutPage::current();
    }

    // حقل صورة موحّد: PNG/JPG/WebP فقط، حد الحجم 2MB، وتصغير في المتصفح قبل الرفع على قرص public.
    // وضع contain يحفظ نسبة الصورة (لا اقتصاص)، والأبعاد الهدف تمنع رفع صور ضخمة تبطئ الصفحة.
    // يُستعمل لصور الغلاف والقصة هنا، وتكرر الموارد الأخرى نفس الإعدادات.
    private static function imageField(string $name, string $label, string $directory, int $width, int $height, int $maxKb = 2048): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->maxSize($maxKb)
            ->imageResizeMode('contain')
            ->imageResizeTargetWidth((string) $width)
            ->imageResizeTargetHeight((string) $height);
    }
}
