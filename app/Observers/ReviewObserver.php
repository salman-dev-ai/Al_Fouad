<?php

namespace App\Observers;

use App\Actions\Reviews\RecalculateProductRating;
use App\Models\Review;
use Illuminate\Support\Facades\Storage;

// يُبقي متوسط تقييم المنتج وعدده مطابقين للتقييمات المعتمدة دائمًا، ويحذف ملفات الصور مع التقييم.
// يعمل مع أي تغيير: إرسال جديد، اعتماد/رفض من الإدارة، تعديل، أو حذف.
// مكان واحد للحساب بدل تكراره في كل مسار.
class ReviewObserver
{
    // بعد الحفظ: أعد الحساب إن كان السجل جديدًا أو تغيّرت حالته أو درجته.
    public function saved(Review $review): void
    {
        if ($review->wasRecentlyCreated || $review->wasChanged(['status', 'rating'])) {
            app(RecalculateProductRating::class)->execute($review->product_id);
        }
    }

    // قبل الحذف: احذف ملفات الصور من القرص (سجلاتها تُحذف بالتتالي في قاعدة البيانات).
    public function deleting(Review $review): void
    {
        foreach ($review->images as $image) {
            Storage::disk('public')->delete($image->path);
        }
    }

    // بعد الحذف: أعد الحساب لأن تقييمًا معتمدًا قد يكون اختفى.
    public function deleted(Review $review): void
    {
        app(RecalculateProductRating::class)->execute($review->product_id);
    }
}
