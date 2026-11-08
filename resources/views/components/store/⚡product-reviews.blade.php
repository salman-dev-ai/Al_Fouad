<?php

use App\Actions\Reviews\SubmitReview;
use App\Enums\ReviewStatus;
use App\Enums\ReviewTag;
use App\Enums\SizeFit;
use App\Models\Product;
use App\Models\Review;
use App\Support\ReviewSettings;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    #[Locked]
    public int $productId;

    // عدد التقييمات المعروضة (مقفل: لا يعدّله المتصفح، ويزيد فقط عبر loadMore).
    #[Locked]
    public int $limit = 6;

    public ?int $rating = null;

    public string $title = '';

    public string $body = '';

    public array $tags = [];

    public string $fit = '';

    public ?int $height = null;

    public ?int $weight = null;

    public array $photos = [];

    public bool $submitted = false;

    // يهيّئ المكوّن بمعرّف المنتج (مقفل) ويملأ النموذج بتقييم العميل السابق إن وُجد (يظهر داخل نافذة التعديل).
    // الصور السابقة لا تُملأ: تبقى ما لم يرفع صورًا جديدة.
    public function mount(int $productId): void
    {
        $this->productId = $productId;

        if ($mine = $this->myReview) {
            $this->rating = $mine->rating;
            $this->title = (string) $mine->title;
            $this->body = $mine->body;
            $this->tags = (array) $mine->tags;
            $size = (array) $mine->size_data;
            $this->fit = (string) ($size['fit'] ?? '');
            $this->height = isset($size['height']) ? (int) $size['height'] : null;
            $this->weight = isset($size['weight']) ? (int) $size['weight'] : null;
        }
    }

    // ملخص التقييمات المعتمدة باستعلام واحد: توزيع الدرجات 5..0، ومنه العدد والمتوسط (بلا استعلام منتج).
    // يطابق المتوسط المخزَّن في جدول المنتجات (مقرَّب لمنزلتين ثم يُعرض بمنزلة).
    #[Computed]
    public function summary(): array
    {
        $counts = Review::query()->approved()->where('product_id', $this->productId)
            ->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating');

        $distribution = collect([5, 4, 3, 2, 1, 0])
            ->mapWithKeys(fn (int $star): array => [$star => (int) ($counts[$star] ?? 0)])
            ->all();

        $count = array_sum($distribution);
        $sum = 0;
        foreach ($distribution as $star => $total) {
            $sum += $star * $total;
        }

        return [
            'count' => $count,
            'average' => $count > 0 ? round($sum / $count, 2) : 0.0,
            'distribution' => $distribution,
        ];
    }

    // تقييم العميل الحالي لهذا المنتج (أي حالة) أو null. اسم المقيّم يُعيَّن من المستخدم الحالي بلا استعلام إضافي.
    #[Computed]
    public function myReview(): ?Review
    {
        if (! auth()->check()) {
            return null;
        }

        $review = Review::query()->with('images')
            ->where('user_id', auth()->id())->where('product_id', $this->productId)->first();

        $review?->setRelation('user', auth()->user());

        return $review;
    }

    // التقييمات المعتمدة لغير العميل نفسه: نجلب limit+1 لمعرفة وجود المزيد بلا استعلام عدّ إضافي.
    #[Computed]
    public function others()
    {
        return Review::query()->approved()
            ->where('product_id', $this->productId)
            ->when($this->myReview, fn ($q, $mine) => $q->whereKeyNot($mine->id))
            ->with(['user:id,name', 'images'])
            ->latest('id')
            ->limit(min(max($this->limit, 1), 60) + 1)
            ->get();
    }

    // هل العميل مؤهل للتقييم/التعديل؟ طلب مدفوع منضج ضمن نافذة 6 أشهر (التنفيذ يعيد التحقق).
    #[Computed]
    public function eligible(): bool
    {
        return auth()->check()
            && app(SubmitReview::class)->eligibleOrderId(auth()->id(), $this->productId) !== null;
    }

    // سبب عدم الأهلية بالعربية (يُحسب فقط لمن لا يحق له التقييم).
    #[Computed]
    public function ineligibleReason(): string
    {
        return app(SubmitReview::class)->ineligibilityReason((int) auth()->id(), $this->productId);
    }

    // يعرض 6 تقييمات إضافية (بحد أقصى 60).
    public function loadMore(): void
    {
        $this->limit = min($this->limit + 6, 60);
        unset($this->others);
    }

    // يستقبل التقييم: تحقق (درجة 0-5، نص 3-100 كلمة بلا روابط، وسوم، مقاس، صور) ثم SubmitReview، ويغلق النافذة عند النجاح.
    // المحاولات محدودة 5 في الساعة لكل عميل.
    public function submit(SubmitReview $submit): void
    {
        abort_unless(auth()->check(), 403);

        $key = 'review:' . auth()->id();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('review', 'محاولات كثيرة، حاول لاحقًا.');

            return;
        }

        RateLimiter::hit($key, 3600);

        if (! ReviewSettings::photosEnabled()) {
            $this->photos = [];
        }

        $noLinks = 'not_regex:/https?:\/\/|www\./i';

        $this->validate([
            'rating' => ['required', 'integer', 'between:0,5'],
            'title' => ['nullable', 'string', 'max:100', $noLinks],
            'body' => ['required', 'string', 'max:1500', $noLinks, function (string $attribute, mixed $value, \Closure $fail): void {
                $words = Review::wordCount((string) $value);

                if ($words < 3) {
                    $fail('اكتب 3 كلمات على الأقل.');
                } elseif ($words > 100) {
                    $fail('الحد الأقصى 100 كلمة.');
                }
            }],
            'tags' => ['array', 'max:5'],
            'tags.*' => [Rule::enum(ReviewTag::class)],
            'fit' => ['nullable', Rule::enum(SizeFit::class)],
            'height' => ['nullable', 'integer', 'between:100,230'],
            'weight' => ['nullable', 'integer', 'between:30,250'],
            'photos' => ['array', 'max:' . ReviewSettings::maxPhotos()],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:' . (ReviewSettings::maxPhotoMb() * 1024)],
        ], [
            'rating.required' => 'اختر عدد النجوم (من 0 إلى 5).',
            'rating.between' => 'اختر عدد النجوم (من 0 إلى 5).',
            'rating.integer' => 'اختر عدد النجوم (من 0 إلى 5).',
            'body.required' => 'اكتب تقييمك.',
            'body.max' => 'النص طويل جدًا.',
            'title.max' => 'الحد الأقصى للعنوان 100 حرف.',
            'title.not_regex' => 'لا يُسمح بالروابط.',
            'body.not_regex' => 'لا يُسمح بالروابط في النص.',
            'tags.max' => 'اختر 5 وسوم كحد أقصى.',
            'tags.*' => 'وسم غير صالح.',
            'fit.*' => 'اختيار المقاس غير صالح.',
            'height.*' => 'الطول بين 100 و230 سم.',
            'weight.*' => 'الوزن بين 30 و250 كغ.',
            'photos.max' => 'الحد الأقصى ' . ReviewSettings::maxPhotos() . ' صور.',
            'photos.*.image' => 'الملف المرفوع ليس صورة.',
            'photos.*.mimes' => 'الصيغ المسموحة: JPG وPNG وWEBP.',
            'photos.*.max' => 'حجم الصورة يجب ألا يتجاوز ' . ReviewSettings::maxPhotoMb() . ' ميغابايت.',
        ]);

        try {
            $submit->execute(
                user: auth()->user(),
                product: Product::query()->findOrFail($this->productId),
                rating: (int) $this->rating,
                title: trim($this->title) ?: null,
                body: trim($this->body),
                tags: $this->tags,
                size: ['fit' => $this->fit, 'height' => $this->height, 'weight' => $this->weight],
                photos: $this->photos,
            );
        } catch (ValidationException $e) {
            $errors = $e->validator->errors();
            $this->addError(
                $errors->has('photos') ? 'photos' : 'review',
                $errors->first('review') ?: ($errors->first('photos') ?: ($errors->first() ?: 'تعذّر إرسال التقييم.')),
            );

            return;
        }

        $this->photos = [];
        unset($this->myReview, $this->others);
        $this->submitted = true;
        $this->dispatch('review-saved');
    }

    // يحذف صورة مختارة (لم تُرسل بعد) من المعاينة.
    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }
};
?>

{{-- هيكل التحميل بنفس أبعاد القسم الحقيقي فلا تقفز الصفحة. --}}
@placeholder
    <div class="mt-12 rounded-3xl border border-line bg-white p-5 sm:p-6" aria-hidden="true">
        <div class="h-7 w-40 animate-pulse rounded bg-surface"></div>
        <div class="mt-6 grid gap-6 lg:grid-cols-[22rem_1fr]">
            <div class="h-56 animate-pulse rounded-2xl bg-surface"></div>
            <div class="h-56 animate-pulse rounded-2xl bg-surface"></div>
        </div>
    </div>
@endplaceholder

@php
    $summary = $this->summary;
    $mine = $this->myReview;
    $others = $this->others;
    $hasMore = $others->count() > $limit;
    $items = ($mine ? collect([$mine]) : collect())->concat($others->take($limit));
    $canEdit = $this->eligible;
@endphp

<div id="reviews" class="mt-12 scroll-mt-24 rounded-3xl border border-line bg-white p-5 shadow-sm sm:p-6"
     x-data="{ all: false }"
     x-on:review-saved.window="$refs.dlg.close()">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-extrabold sm:text-2xl">تقييمات العملاء</h2>

        <div class="flex items-center gap-2">
            @if ($canEdit && ! $mine)
                <button type="button" @click="$refs.dlg.showModal()"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white hover:bg-brand-700">
                    <x-lucide-pencil class="size-4" aria-hidden="true" /> اكتب تقييمًا
                </button>
            @endif

            @if ($items->count() > 1)
                <button type="button" @click="all = !all" :aria-expanded="all"
                        class="inline-flex items-center gap-1 text-sm font-bold text-brand-700 hover:underline">
                    <span x-text="all ? 'عرض أقل' : 'عرض جميع التقييمات'">عرض جميع التقييمات</span>
                    <x-lucide-chevron-left class="size-4" aria-hidden="true" />
                </button>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[22rem_1fr]">

        {{-- ========== بطاقة الملخص ========== --}}
        <div class="rounded-2xl border border-line bg-surface/50 p-5">
            @if ($summary['count'] > 0)
                <div class="text-center">
                    <p class="text-5xl font-extrabold">{{ number_format($summary['average'], 1) }}</p>
                    <div class="mt-2 flex justify-center">
                        <x-store.rating-stars :rating="$summary['average']" size="size-6" />
                    </div>
                    <p class="mt-2 text-sm text-ink-soft">من 5 · {{ $summary['count'] }} تقييم</p>
                </div>

                <ul class="mt-5 space-y-2 text-sm" aria-label="توزيع التقييمات">
                    @foreach ($summary['distribution'] as $star => $total)
                        <li class="flex items-center gap-2" wire:key="dist-{{ $star }}">
                            <span class="w-8 shrink-0 text-ink-soft">{{ $star }} ★</span>
                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-white">
                                <span class="block h-full rounded-full bg-brand-600" style="width: {{ intdiv($total * 100, $summary['count']) }}%"></span>
                            </span>
                            <span class="w-8 shrink-0 text-end font-semibold">{{ $total }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="py-6 text-center">
                    <x-lucide-star class="mx-auto size-10 text-line" aria-hidden="true" />
                    <p class="mt-3 text-sm text-ink-soft">لا توجد تقييمات بعد.</p>
                </div>
            @endif

            {{-- حالة العميل غير المؤهل أو الزائر --}}
            @guest
                <p class="mt-5 border-t border-line pt-4 text-center text-sm text-ink-soft">
                    <a href="{{ route('login') }}" class="font-bold text-brand-700 underline underline-offset-4">سجّل الدخول</a>
                    لتقييم منتج اشتريته.
                </p>
            @endguest
            @auth
                @if (! $canEdit && ! $mine)
                    <p class="mt-5 border-t border-line pt-4 text-center text-sm text-ink-soft">{{ $this->ineligibleReason }}</p>
                @endif
            @endauth
        </div>

        {{-- ========== الشريط / الشبكة ========== --}}
        <div class="min-w-0">
            @if ($items->isEmpty())
                <div class="flex h-full min-h-40 items-center justify-center rounded-2xl border border-dashed border-line p-6 text-center text-sm text-ink-soft">
                    كن أول من يقيّم هذا المنتج بعد استلامه.
                </div>
            @else
                <div class="relative" x-data="{
                        scroll(dir) {
                            const el = $refs.track;
                            const rtl = getComputedStyle(el).direction === 'rtl' ? -1 : 1;
                            el.scrollBy({ left: dir * rtl * el.clientWidth * 0.8, behavior: 'smooth' });
                        },
                    }">
                    <div x-ref="track" tabindex="0" aria-label="قائمة التقييمات"
                         :class="all ? 'grid gap-4 md:grid-cols-2' : 'flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2 [scrollbar-width:none]'">
                        @foreach ($items as $review)
                            @php $isMine = $mine && $review->id === $mine->id; @endphp

                            <article wire:key="review-{{ $review->id }}"
                                     :class="all ? '' : 'w-[88%] shrink-0 snap-start sm:w-96'"
                                     @class([
                                         'rounded-2xl border p-4',
                                         'border-brand-600/40 bg-brand-50/40' => $isMine,
                                         'border-line bg-white' => ! $isMine,
                                     ])>

                                <header class="flex items-start gap-3">
                                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-100 text-base font-extrabold text-brand-700" aria-hidden="true">{{ mb_substr($review->authorName(), 0, 1) }}</span>

                                    <div class="min-w-0 flex-1">
                                        <p class="flex flex-wrap items-center gap-x-2 text-sm font-extrabold">
                                            {{ $review->authorName() }}
                                            <x-lucide-badge-check class="size-4 text-brand-600" aria-label="مشترٍ موثّق" />
                                            @if ($isMine) <span class="rounded-full bg-brand-600 px-2 py-0.5 text-[11px] font-bold text-white">تقييمك</span> @endif
                                        </p>
                                        <p class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-ink-soft">
                                            <time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('Y-m-d') }}</time>
                                            <x-store.rating-stars :rating="$review->rating" />
                                        </p>
                                    </div>

                                    @if ($isMine && $canEdit)
                                        <button type="button" @click="$refs.dlg.showModal()" aria-label="تعديل تقييمك" title="تعديل تقييمك"
                                                class="grid size-9 shrink-0 place-items-center rounded-full border border-line bg-white text-ink-soft transition hover:border-brand-600 hover:text-brand-700">
                                            <x-lucide-pencil class="size-4" aria-hidden="true" />
                                        </button>
                                    @endif
                                </header>

                                @if ($isMine)
                                    <p class="mt-3 inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold
                                        {{ match ($review->status) {
                                            ReviewStatus::Approved => 'bg-brand-100 text-brand-700',
                                            ReviewStatus::Rejected => 'bg-danger/10 text-danger',
                                            default => 'bg-amber-100 text-amber-800',
                                        } }}">
                                        {{ $review->status->getLabel() }}
                                        @if ($review->status === ReviewStatus::Pending) · لا يظهر للعامة قبل اعتماده @endif
                                    </p>
                                @endif

                                @if (filled($review->title))
                                    <h3 class="mt-3 text-sm font-extrabold">{{ $review->title }}</h3>
                                @endif

                                <p class="mt-2 whitespace-pre-line text-sm leading-7">{{ $review->body }}</p>

                                @if ($review->tagLabels() !== [])
                                    <ul class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach ($review->tagLabels() as $label)
                                            <li class="rounded-full bg-surface px-2.5 py-0.5 text-xs font-semibold text-ink-soft">{{ $label }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($review->sizeSummary())
                                    <p class="mt-2 text-xs text-ink-soft">{{ $review->sizeSummary() }}</p>
                                @endif

                                @if ($review->images->isNotEmpty())
                                    <ul class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($review->images as $image)
                                            <li wire:key="rimg-{{ $image->id }}">
                                                <a href="{{ $image->url() }}" target="_blank" rel="noopener">
                                                    <img src="{{ $image->url() }}" alt="صورة من تقييم {{ $review->authorName() }}" width="64" height="64" loading="lazy" decoding="async" class="size-16 rounded-lg object-cover">
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    {{-- أسهم الشريط (تختفي في وضع الشبكة) --}}
                    <div x-show="! all" class="mt-3 flex justify-center gap-2">
                        <button type="button" @click="scroll(-1)" aria-label="السابق" class="grid size-10 place-items-center rounded-full border border-line bg-white hover:bg-surface">
                            <x-lucide-chevron-right class="size-5" aria-hidden="true" />
                        </button>
                        <button type="button" @click="scroll(1)" aria-label="التالي" class="grid size-10 place-items-center rounded-full border border-line bg-white hover:bg-surface">
                            <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                        </button>
                    </div>

                    @if ($hasMore)
                        <div x-show="all" class="mt-5 text-center">
                            <button type="button" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore"
                                    class="rounded-xl border border-line px-6 py-2.5 text-sm font-bold hover:bg-surface disabled:opacity-60">
                                <span wire:loading.remove wire:target="loadMore">عرض المزيد</span>
                                <span wire:loading wire:target="loadMore">جارٍ التحميل…</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- ========== نافذة التقييم/التعديل (dialog أصلي: Esc وحبس التركيز مجانًا) ========== --}}
    @if ($canEdit)
        <dialog x-ref="dlg" wire:ignore.self @click.self="$refs.dlg.close()"
                class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl bg-white p-0 shadow-2xl backdrop:bg-ink/50">
            <div class="max-h-[85vh] overflow-y-auto p-5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-extrabold">{{ $mine ? 'تعديل تقييمك' : 'قيّم هذا المنتج' }}</h3>
                    <button type="button" @click="$refs.dlg.close()" aria-label="إغلاق" class="grid size-9 place-items-center rounded-full hover:bg-surface">
                        <x-lucide-x class="size-5" aria-hidden="true" />
                    </button>
                </div>

                @if ($mine?->status === ReviewStatus::Approved)
                    <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">تعديل التقييم المعتمد يعيده «قيد المراجعة» حتى يُعتمد من جديد.</p>
                @endif

                <form wire:submit="submit" class="mt-4 space-y-4" novalidate>
                    {{-- النجوم من 0 إلى 5 --}}
                    <fieldset x-data="{ r: {{ $rating === null ? -1 : (int) $rating }} }">
                        <legend class="mb-1 text-sm font-semibold">عدد النجوم (من 0 إلى 5)</legend>
                        <div class="flex items-center gap-1">
                            @foreach ([0, 1, 2, 3, 4, 5] as $i)
                                <label class="cursor-pointer" wire:key="star-{{ $i }}">
                                    <input type="radio" name="rating" value="{{ $i }}" wire:model="rating" x-model.number="r" class="peer sr-only">
                                    <span class="sr-only">{{ $i }} من 5</span>
                                    @if ($i === 0)
                                        <span class="me-2 inline-flex size-8 items-center justify-center rounded-full border border-line text-xs font-bold peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500"
                                              x-bind:class="r === 0 ? 'bg-brand-600 text-white' : 'bg-white text-ink-soft'" aria-hidden="true">0</span>
                                    @else
                                        <x-lucide-star class="size-8 rounded peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500"
                                                       x-bind:class="r >= {{ $i }} ? 'fill-brand-600 text-brand-600' : 'text-line'" aria-hidden="true" />
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        @error('rating') <p class="mt-1 text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror
                    </fieldset>

                    <div>
                        <label for="review-title" class="mb-1 block text-sm font-semibold">عنوان (اختياري)</label>
                        <input id="review-title" type="text" wire:model="title" maxlength="100"
                               class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand-500">
                        @error('title') <p class="mt-1 text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror
                    </div>

                    {{-- النص مع عدّاد كلمات مباشر (3 إلى 100) --}}
                    <div x-data="{ words: 0, count(t) { const m = t.trim().match(/\S+/g); return m ? m.length : 0 } }"
                         x-init="words = count($el.querySelector('textarea').value)">
                        <label for="review-body" class="mb-1 block text-sm font-semibold">تقييمك</label>
                        <textarea id="review-body" wire:model="body" rows="4" x-on:input="words = count($event.target.value)"
                                  class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand-500"></textarea>
                        <p class="mt-1 text-xs" x-bind:class="(words < 3 || words > 100) ? 'text-danger' : 'text-ink-soft'">
                            <span x-text="words"></span> / 100 كلمة (الحد الأدنى 3)
                        </p>
                        @error('body') <p class="mt-1 text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <fieldset>
                        <legend class="mb-1 text-sm font-semibold">وسوم (اختياري)</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach (ReviewTag::cases() as $tag)
                                <label class="cursor-pointer" wire:key="tag-{{ $tag->value }}">
                                    <input type="checkbox" value="{{ $tag->value }}" wire:model="tags" class="peer sr-only">
                                    <span class="inline-block rounded-full border border-line bg-white px-3 py-1 text-xs font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">{{ $tag->getLabel() }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('tags') <p class="mt-1 text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror
                        @error('tags.*') <p class="mt-1 text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror
                    </fieldset>

                    <fieldset class="space-y-2">
                        <legend class="mb-1 text-sm font-semibold">بيانات المقاس (اختياري، تظهر مع تقييمك)</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach (SizeFit::cases() as $option)
                                <label class="cursor-pointer" wire:key="fit-{{ $option->value }}">
                                    <input type="radio" name="fit" value="{{ $option->value }}" wire:model="fit" class="peer sr-only">
                                    <span class="inline-block rounded-full border border-line bg-white px-3 py-1 text-xs font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">{{ $option->getLabel() }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="review-height" class="mb-1 block text-xs font-semibold">الطول (سم)</label>
                                <input id="review-height" type="number" inputmode="numeric" wire:model="height" min="100" max="230"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand-500">
                            </div>
                            <div>
                                <label for="review-weight" class="mb-1 block text-xs font-semibold">الوزن (كغ)</label>
                                <input id="review-weight" type="number" inputmode="numeric" wire:model="weight" min="30" max="250"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand-500">
                            </div>
                        </div>
                        @error('fit') <p class="text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror
                        @error('height') <p class="text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror
                        @error('weight') <p class="text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror
                    </fieldset>

                    @if (ReviewSettings::photosEnabled())
                        <div>
                            <label for="review-photos" class="mb-1 block text-sm font-semibold">
                                صور (اختياري، حتى {{ ReviewSettings::maxPhotos() }} بحجم {{ ReviewSettings::maxPhotoMb() }} ميغابايت للصورة)
                            </label>
                            <input id="review-photos" type="file" wire:model="photos" multiple accept="image/jpeg,image/png,image/webp"
                                   class="block w-full text-sm file:me-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-2 file:text-sm file:font-bold file:text-white">
                            <p class="mt-1 text-xs text-ink-soft" wire:loading wire:target="photos">جارٍ رفع الصور…</p>

                            @if ($mine && $mine->images->isNotEmpty() && $photos === [])
                                <p class="mt-1 text-xs text-ink-soft">لديك {{ $mine->images->count() }} صور مرفقة. رفع صور جديدة يستبدلها.</p>
                            @endif

                            @if ($photos !== [])
                                <ul class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($photos as $index => $photo)
                                        @if (method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                                            <li class="relative" wire:key="photo-{{ $index }}">
                                                <img src="{{ $photo->temporaryUrl() }}" alt="معاينة الصورة {{ $index + 1 }}" class="size-16 rounded-lg object-cover">
                                                <button type="button" wire:click="removePhoto({{ $index }})"
                                                        class="absolute -end-1 -top-1 inline-flex size-5 items-center justify-center rounded-full bg-danger text-xs font-bold text-white"
                                                        aria-label="حذف الصورة {{ $index + 1 }}">×</button>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif

                            @error('photos') <p class="mt-1 text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror
                            @error('photos.*') <p class="mt-1 text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @error('review') <p class="text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror

                    <div class="flex items-center justify-end gap-2 border-t border-line pt-4">
                        <button type="button" @click="$refs.dlg.close()" class="rounded-xl px-4 py-2.5 text-sm font-bold text-ink-soft hover:bg-surface">إلغاء</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="submit,photos"
                                class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="submit">{{ $mine ? 'حفظ التعديل' : 'إرسال التقييم' }}</span>
                            <span wire:loading wire:target="submit">جارٍ الإرسال…</span>
                        </button>
                    </div>
                </form>
            </div>
        </dialog>
    @endif
</div>
