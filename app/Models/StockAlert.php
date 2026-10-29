<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Attributes\Casts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// طلب تنبيه توفر لخيار منتج (لون + مقاس) من عميل واحد برقم هاتفه.
// تنبيه لمرة واحدة بموافقة صريحة، تُحذف بياناته تلقائيًا بعد انتهاء الغرض (Prunable).
// يُنشأ من مكوّن صفحة المنتج، ويُدار من لوحة الإدارة.
#[Fillable(['product_variant_id', 'user_id', 'phone', 'consent_at', 'available_at', 'notified_at'])]
#[Casts(['consent_at' => 'datetime', 'available_at' => 'datetime', 'notified_at' => 'datetime'])]
class StockAlert extends Model
{
    use Prunable;

    public const MAX_PENDING_PER_PHONE = 10;

    // الخيار المطلوب تنبيهه (لونه ومقاسه ومنتجه).
    // يُحمَّل مسبقًا في جدول الإدارة لتجنب N+1.
    // الحذف المتتالي يزيل الطلب إن حُذف الخيار.
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // حساب العميل إن كان مسجّلًا وقت الطلب (اختياري).
    // يصير null إن حُذف الحساب (nullOnDelete).
    // لا يُستعمل في منطق الإرسال، فقط للمرجع.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // يعلّم كل التنبيهات المعلّقة لخيار ما بأنها «متوفر الآن» (مرة واحدة لكل تنبيه).
    // يُستدعى من مراقب ProductVariant عند عودة المخزون، ويصلح للاستدعاء من أي مسار يعيد المخزون.
    // استعلام واحد لا حلقة، ولا يمس تنبيهًا أُرسل أو عُلِّم سابقًا.
    public static function markAvailable(int $variantId): int
    {
        return static::query()
            ->where('product_variant_id', $variantId)
            ->whereNull('notified_at')
            ->whereNull('available_at')
            ->update(['available_at' => now()]);
    }

    // رابط واتساب برسالة جاهزة للعميل (رقمه بصيغة دولية بلا +، والنص مشفّر للرابط).
    // الرسالة تذكر أنه تنبيه لمرة واحدة بناءً على طلبه، وتتضمن رابط المنتج.
    // يستعمله زر «واتساب» في لوحة الإدارة.
    public function whatsAppUrl(): string
    {
        $variant = $this->variant;
        $product = $variant->product;

        $text = "مرحبًا، يسعدنا إبلاغك أن «{$product->name}» ({$variant->color} / {$variant->size}) عاد متوفرًا في الفؤاد.\n"
            . route('products.show', $product) . "\n"
            . 'هذا تنبيه لمرة واحدة بناءً على طلبك.';

        return 'https://wa.me/' . PhoneNumber::digits($this->phone) . '?text=' . rawurlencode($text);
    }

    // السجلات التي يجب حذفها: ما أُرسل منذ 30 يومًا، وما لم يُرسل منذ 180 يومًا (تقليل البيانات المحفوظة).
    // يُنفَّذ بالأمر model:prune المجدول يوميًا.
    // لا يحذف تنبيهًا ما زال ضمن مدته.
    public function prunable(): Builder
    {
        return static::query()
            ->where(fn (Builder $q) => $q->where('notified_at', '<', now()->subDays(30))
                ->orWhere('created_at', '<', now()->subDays(180)));
    }
}
