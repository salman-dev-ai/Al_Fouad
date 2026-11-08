<?php

namespace App\Actions\Reviews;

use App\Models\Review;

// نقطة الامتداد للنشر التلقائي مستقبلًا، وهي معطّلة الآن (تعيد false دائمًا) فكل تقييم يمر بالمراجعة.
// قاعدة ثابتة: القرار لا يقرأ درجة النجوم أبدًا (0 إلى 5 سواسية)، ويعتمد فقط على أهلية الشراء
// وفحوص المحتوى (روابط، كلمات ممنوعة، تكرار) ومؤشرات المخاطر (حساب جديد، معدل إرسال، تشابه نصوص).
// عند تفعيله لاحقًا يُستدعى من SubmitReview بعد الحفظ، ويعتمد عبر forceFill كالإدارة.
class EvaluateReviewForAutoApproval
{
    public function shouldApprove(Review $review): bool
    {
        return false;
    }
}
