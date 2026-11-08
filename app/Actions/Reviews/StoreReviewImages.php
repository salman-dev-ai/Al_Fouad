<?php

namespace App\Actions\Reviews;

use App\Models\Review;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

// يعالج صور التقييم ويحفظها: يفكّ الصورة ويعيد ترميزها WebP بحد أقصى 1600 بكسل للضلع الأطول.
// إعادة الترميز تزيل EXIF (الموقع والجهاز) وأي حمولة مخفية داخل الملف، والأسماء عشوائية.
// عند فشل أي صورة تُحذف ملفات هذه الدفعة ويُرمى خطأ تحقق عربي.
class StoreReviewImages
{
    private const DISK = 'public';

    private const MAX_SIDE = 1600;

    private const MAX_PIXELS = 40_000_000;

    // يستبدل صور التقييم بالجديدة: يخزّن الجديدة أولًا ثم يحذف القديمة (ملفات وسجلات) بعد النجاح.
    // لا يُستدعى إلا حين يرفع العميل صورًا جديدة، وإلا بقيت القديمة.
    // يعيد عدد الصور المحفوظة.
    public function replace(Review $review, array $files): int
    {
        $old = $review->images()->get();
        $paths = [];

        try {
            foreach (array_values($files) as $index => $file) {
                $path = $this->encode($file);
                $paths[] = $path;
                $review->images()->create([
                    'path' => $path,
                    'size' => (int) Storage::disk(self::DISK)->size($path),
                    'sort' => $index,
                ]);
            }
        } catch (Throwable) {
            foreach ($paths as $path) {
                Storage::disk(self::DISK)->delete($path);
            }

            throw ValidationException::withMessages(['photos' => 'تعذّرت معالجة إحدى الصور، جرّب صورة أخرى.']);
        }

        foreach ($old as $image) {
            Storage::disk(self::DISK)->delete($image->path);
            $image->delete();
        }

        return count($paths);
    }

    // يحوّل ملفًا مرفوعًا إلى WebP نظيف ويعيد مساره على القرص.
    // يرفض الصور الضخمة جدًا بالبكسلات (حماية الذاكرة) والملفات التي لا تُفك كصورة.
    // يحافظ على الشفافية للـ PNG.
    private function encode($file): string
    {
        $raw = file_get_contents($file->getRealPath());
        $image = $raw === false ? false : @imagecreatefromstring($raw);

        if ($image === false) {
            throw new RuntimeException('not an image');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width * $height > self::MAX_PIXELS) {
            throw new RuntimeException('too large');
        }

        $scale = min(1, self::MAX_SIDE / max($width, $height));
        if ($scale < 1) {
            $scaled = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
            if ($scaled === false) {
                throw new RuntimeException('resize failed');
            }
            $image = $scaled;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, 82);
        $data = (string) ob_get_clean();

        if ($data === '') {
            throw new RuntimeException('encode failed');
        }

        $path = 'reviews/' . now()->format('Y/m') . '/' . Str::random(40) . '.webp';
        Storage::disk(self::DISK)->put($path, $data);

        return $path;
    }
}
