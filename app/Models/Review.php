<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Enums\ReviewTag;
use App\Enums\SizeFit;
use App\Observers\ReviewObserver;
 use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

// تقييم عميل لمنتج اشتراه فعلًا (مرتبط بطلب مؤهل).
// الحالة status خارج الحقول القابلة للتعبئة عمدًا: لا تتغير إلا بكود صريح (إجراء الإرسال أو الإدارة).
// المراقب يعيد حساب متوسط المنتج كلما تغيّرت الحالة أو التقييم أو حُذف السجل.
#[Fillable(['product_id', 'user_id', 'order_id', 'rating', 'title', 'body', 'tags', 'size_data'])]
#[ObservedBy(ReviewObserver::class)]
class Review extends Model
{


    // تحويلات الأعمدة: الدرجة عدد صحيح، الحالة Enum، الوسوم وبيانات المقاس مصفوفات تُخزَّن JSON.
    // تُعرَّف بدالة casts القياسية في Laravel (تعمل في كل الإصدارات).
    // بدونها تُمرَّر المصفوفات خامًا إلى قاعدة البيانات فيفشل الحفظ.
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'reviewed_at' => 'datetime',
            'tags' => 'array',
            'size_data' => 'array',
        ];
    }
    // عدد كلمات نص (يدعم العربية والمسافات المتعددة).
    // مصدر واحد للعدّ تستعمله الواجهة والإجراء معًا.
    // نص فارغ = 0.
    public static function wordCount(string $text): int
    {
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    // المنتج المقيَّم.
    // الحذف المتتالي يزيل تقييماته إن حُذف المنتج.
    // يُحمَّل في جدول الإدارة.
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // صاحب التقييم.
    // لا نعرض منه علنًا إلا الاسم الأول.
    // الحذف المتتالي يزيل تقييماته إن حُذف حسابه.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // الطلب المؤهل الذي أثبت الشراء.
    // يصير null إن حُذف الطلب (nullOnDelete) ويبقى التقييم.
    // للمرجع فقط، ولا يُعرض علنًا.
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // صور التقييم بترتيبها.
    // لا تُعرض علنًا إلا مع تقييم معتمد (القائمة العامة تجلب المعتمد فقط).
    // يحذف المراقب ملفاتها عند حذف التقييم.
    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class)->orderBy('sort');
    }

    // نطاق التقييمات المعتمدة فقط (المنشورة علنًا والداخلة في المتوسط).
    // يُستعمل في صفحة المنتج وفي حساب المتوسط.
    // مثال: Review::query()->approved()->where('product_id', $id).
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Approved->value);
    }

    // اسم المقيِّم للعرض العلني: الاسم الأول فقط حماية لخصوصيته.
    // يعود إلى «عميل» إن لم يتوفر اسم.
    // الناتج يُهرَّب في Blade فلا خطر منه.
    public function authorName(): string
    {
        $first = Str::of((string) $this->user?->name)->trim()->before(' ')->limit(20, '')->toString();

        return $first !== '' ? $first : 'عميل';
    }

    // تسميات وسوم التعليق المخزَّنة (يتجاهل أي قيمة غير معروفة).
    // للعرض كشارات في القائمة العامة والإدارة.
    // تعيد مصفوفة فارغة إن لم توجد وسوم.
    public function tagLabels(): array
    {
        return collect((array) $this->tags)
            ->map(fn ($tag): ?string => ReviewTag::tryFrom((string) $tag)?->getLabel())
            ->filter()
            ->values()
            ->all();
    }

    // سطر بيانات المقاس للعرض (الملاءمة والطول والوزن إن وُجدت) أو null.
    // الأرقام تُحوَّل إلى أعداد صحيحة قبل العرض.
    // مثال: «المقاس: مناسب · الطول 170 سم · الوزن 65 كغ».
    public function sizeSummary(): ?string
    {
        $data = (array) $this->size_data;
        $parts = [];

        if ($fit = SizeFit::tryFrom((string) ($data['fit'] ?? ''))) {
            $parts[] = 'المقاس: ' . $fit->getLabel();
        }
        if (! empty($data['height'])) {
            $parts[] = 'الطول ' . (int) $data['height'] . ' سم';
        }
        if (! empty($data['weight'])) {
            $parts[] = 'الوزن ' . (int) $data['weight'] . ' كغ';
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
