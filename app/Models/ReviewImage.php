<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

// صورة مرفقة بتقييم (معالَجة ومعاد ترميزها WebP).
// المسار نسبي على القرص public، والرابط العلني يُبنى منه.
// الحذف المتتالي في قاعدة البيانات، وحذف الملف يتولاه مراقب التقييم أو إجراء الاستبدال.
#[Fillable(['review_id', 'path', 'size', 'sort'])]
class ReviewImage extends Model
{
    // التقييم صاحب الصورة.
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    // الرابط العلني للصورة.
    // يتطلب php artisan storage:link مرة واحدة.
    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
