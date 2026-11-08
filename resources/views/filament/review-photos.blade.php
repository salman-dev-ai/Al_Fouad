{{-- عرض صور تقييم داخل نافذة الإدارة: شبكة مربعة، والنقر يفتح الصورة كاملة في تبويب جديد. --}}
<div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
    @foreach ($review->images as $image)
        <a href="{{ $image->url() }}" target="_blank" rel="noopener">
            <img src="{{ $image->url() }}" alt="صورة التقييم" class="aspect-square w-full rounded-lg object-cover">
        </a>
    @endforeach
</div>
