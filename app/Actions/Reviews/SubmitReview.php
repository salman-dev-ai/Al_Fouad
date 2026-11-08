<?php

namespace App\Actions\Reviews;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Enums\ReviewTag;
use App\Enums\SizeFit;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Support\ReviewSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// يسجّل تقييم عميل لمنتج بشرط أن يكون اشتراه ودفع ثمنه، ويضعه قيد المراجعة دائمًا.
// الأهلية: طلب مدفوع غير ملغى حالته «تم التسليم» أو مرّ 20 يومًا على تقديمه، وضمن 6 أشهر من التسليم.
// هو المكان الوحيد الذي يُنشئ التقييمات، فلا يتجاوز أي مسار شروط الشراء ولا حدود الدرجة والكلمات.
class SubmitReview
{
    // عمود حالة الدفع وقيمته «مدفوع»: غيّرهما هنا فقط إن اختلفا في مخططكم.
    private const PAYMENT_COLUMN = 'payment_status';

    private const PAID_VALUE = 'paid';

    private const WAIT_DAYS = 20;

    private const WINDOW_MONTHS = 6;

    // طلبات العميل غير الملغاة التي تحوي المنتج (قاعدة مشتركة لكل الفحوص).
    // مقيَّدة بالعميل نفسه فلا يمكن استعمال طلب غيره.
    private function baseQuery(int $userId, int $productId): Builder
    {
        return Order::query()
            ->where('user_id', $userId)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->whereHas('items', fn ($q) => $q->where('product_id', $productId));
    }

    // الطلبات المنضجة: التسليم تم، أو مرّت 20 يومًا على تقديمها.
    private function matureScope(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q
            ->where('status', OrderStatus::Delivered->value)
            ->orWhere('created_at', '<=', now()->subDays(self::WAIT_DAYS)));
    }


        // نطاق الطلبات المدفوعة: مدفوع إلكترونيًا، أو دفع عند الاستلام وقد سُلِّم (الاستلام = لحظة الدفع).
    // الدفع عند الاستلام غير المسلَّم لا يُعدّ مدفوعًا، فلا يتاح تقييمه قبل التسليم.
    // مقيَّد بالطلبات المعروضة فقط، ولا يغيّر أي بيانات.
    private function paidScope(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q
            ->where(self::PAYMENT_COLUMN, self::PAID_VALUE)
            ->orWhere(fn ($cod) => $cod
                ->where('payment_method', 'cod')
                ->where('status', OrderStatus::Delivered->value)));
    }

    // رقم أحدث طلب مؤهل للتقييم لهذا العميل والمنتج، أو null.
    // الشروط: مدفوع، ومنضج (تسليم أو 20 يومًا)، وداخل نافذة 6 أشهر من التسليم (وإلا من تاريخ الطلب).
    // تغذي الواجهة والتنفيذ معًا فلا يختلفان.
    public function eligibleOrderId(int $userId, int $productId): ?int
    {
            $query = $this->paidScope($this->baseQuery($userId, $productId));

        $id = $this->matureScope($query)
            ->whereRaw('COALESCE(orders.delivered_at, orders.created_at) >= ?', [now()->subMonthsNoOverflow(self::WINDOW_MONTHS)])
            ->latest('id')
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    // سبب عدم الأهلية بالعربية ليعرضه النموذج بدل رسالة عامة.
    // يفحص بالتدرج: لا طلب، ثم غير مدفوع، ثم لم يُسلَّم/لم تمض 20 يومًا، وأخيرًا انتهت النافذة.
    // يُستدعى فقط حين تعيد eligibleOrderId قيمة null.
    public function ineligibilityReason(int $userId, int $productId): string
    {
        $base = $this->baseQuery($userId, $productId);

        if (! (clone $base)->exists()) {
            return 'يمكن تقييم المنتج بعد شرائه واستلامه.';
        }

        $paid = $this->paidScope(clone $base);
        if (! (clone $paid)->exists()) {
            return 'يتاح التقييم بعد إتمام دفع الطلب.';
        }

        if (! $this->matureScope(clone $paid)->exists()) {
            return 'يتاح التقييم بعد تسليم الطلب، أو بعد مرور ' . self::WAIT_DAYS . ' يومًا على تقديمه.';
        }

        return 'انتهت مدة التقييم (' . self::WINDOW_MONTHS . ' أشهر).';
    }

    // ينفّذ الإرسال: يتحقق من الأهلية والحدود، ثم ينشئ أو يحدّث التقييم بحالة «قيد المراجعة».
    // تقييم موجود لنفس العميل والمنتج يُحدَّث ويعود للمراجعة، والصور الجديدة (إن وُجدت) تستبدل القديمة.
    // يرمي ValidationException برسالة عربية إن لم يحقق الشرط.
    public function execute(
        User $user,
        Product $product,
        int $rating,
        ?string $title,
        string $body,
        array $tags = [],
        array $size = [],
        array $photos = [],
    ): Review {
        if (! $product->is_active) {
            throw ValidationException::withMessages(['review' => 'هذا المنتج غير متاح للتقييم.']);
        }

        if ($rating < 0 || $rating > 5) {
            throw ValidationException::withMessages(['rating' => 'اختر عدد النجوم من 0 إلى 5.']);
        }

        $words = Review::wordCount($body);
        if ($words < 3 || $words > 100) {
            throw ValidationException::withMessages(['body' => 'النص من 3 إلى 100 كلمة.']);
        }

        $orderId = $this->eligibleOrderId($user->id, $product->id);

        if ($orderId === null) {
            throw ValidationException::withMessages([
                'review' => $this->ineligibilityReason($user->id, $product->id),
            ]);
        }

        $photos = ReviewSettings::photosEnabled()
            ? array_slice(array_values($photos), 0, ReviewSettings::maxPhotos())
            : [];

        return DB::transaction(function () use ($user, $product, $orderId, $rating, $title, $body, $tags, $size, $photos): Review {
            $review = Review::query()->firstOrNew(['user_id' => $user->id, 'product_id' => $product->id]);

            $review->fill([
                'order_id' => $orderId,
                'rating' => $rating,
                'title' => filled($title) ? $title : null,
                'body' => $body,
                'tags' => $this->cleanTags($tags),
                'size_data' => $this->cleanSize($size),
            ]);
            $review->status = ReviewStatus::Pending;
            $review->reviewed_at = null;
            $review->save();

            if ($photos !== []) {
                app(StoreReviewImages::class)->replace($review, $photos);
            }

            return $review;
        });
    }

    // يبقي الوسوم المعرّفة فقط، بلا تكرار، بحد أقصى 5.
    // أي قيمة غريبة تُهمل بصمت، ولا يُخزَّن نص حر.
    // تعيد null إن لم يبقَ شيء.
    private function cleanTags(array $tags): ?array
    {
        $clean = collect($tags)
            ->map(fn ($tag): ?string => ReviewTag::tryFrom((string) $tag)?->value)
            ->filter()
            ->unique()
            ->take(5)
            ->values()
            ->all();

        return $clean === [] ? null : $clean;
    }

    // ينظّف بيانات المقاس: الملاءمة من القائمة المغلقة، والطول 100-230 سم، والوزن 30-250 كغ.
    // القيم خارج النطاق تُهمل، وتعيد null إن لم يبقَ شيء.
    // كلها اختيارية.
    private function cleanSize(array $size): ?array
    {
        $height = (int) ($size['height'] ?? 0);
        $weight = (int) ($size['weight'] ?? 0);

        $clean = array_filter([
            'fit' => SizeFit::tryFrom((string) ($size['fit'] ?? ''))?->value,
            'height' => $height >= 100 && $height <= 230 ? $height : null,
            'weight' => $weight >= 30 && $weight <= 250 ? $weight : null,
        ], fn ($value): bool => $value !== null);

        return $clean === [] ? null : $clean;
    }
}
