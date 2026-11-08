<?php

namespace App\View\Components\Layout;

use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AnnouncementBar extends Component
{
    /**
     * إعدادات المحل الحالية، وتُقرأ مرة واحدة عند إنشاء المكوّن.
     * قد تكون null إذا لم يُنشئ المدير سجل الإعدادات بعد.
     */
    public ?StoreSetting $settings;

    /**
     * جلب سجل الإعدادات من قاعدة البيانات.
     * بدونه لا يصل نص الإعلان الذي يكتبه المدير إلى الواجهة.
     */
    public function __construct()
    {
        $this->settings = StoreSetting::current();
    }

    /**
     * تحديد القالب الذي يُرسم بواسطة هذا المكوّن.
     * شرط ظهور الشريط من عدمه (التفعيل + وجود نص) مُنفَّذ داخل القالب
     * لتفادي طباعة شريط فارغ حين يكون الإعلان معطّلًا أو نصّه فارغًا.
     */
    public function render(): View|\Closure|string
    {
        return view('components.layout.announcement-bar');
    }
}
