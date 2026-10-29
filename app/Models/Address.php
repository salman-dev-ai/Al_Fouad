<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Casts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

// عنوان شحن يملكه مستخدم واحد (النمط الحديث في Laravel 13: خصائص PHP بدل $fillable و casts()).
// user_id غير قابل للتعبئة الجماعية عمدًا، فيُضبط فقط عبر علاقة المستخدم (addresses()->create).
// الحد الأقصى للعناوين لكل مستخدم ثابت هنا ليُستعمل في الواجهة والتحقق.
#[Fillable(['label', 'recipient_name', 'phone', 'city', 'address_line', 'notes', 'location_url', 'is_default'])]
#[Casts(['is_default' => 'boolean'])]
class Address extends Model
{
    public const MAX_PER_USER = 10;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // يجعل هذا العنوان هو الافتراضي الوحيد لصاحبه، داخل معاملة واحدة.
    // يلغي الافتراضي عن بقية عناوين المستخدم أولًا حتى لا يوجد عنوانان افتراضيان أبدًا.
    // آمن للاستدعاء المتكرر.
    public function makeDefault(): void
    {
        DB::transaction(function (): void {
            static::query()
                ->where('user_id', $this->user_id)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);

            $this->forceFill(['is_default' => true])->save();
        });
    }
}