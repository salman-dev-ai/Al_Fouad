<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('طلباتي')] class extends Component {
    use WithPagination;

    // طلبات المستخدم الحالي فقط، الأحدث أولًا، بترقيم 10 في الصفحة.
    // الأصناف تُحمَّل مسبقًا (with) لعرض ملخصها دون استعلام لكل طلب (منع N+1).
    // لا تقبل أي معرّف مستخدم من المتصفح، ولا تتضمن طلبات الزائر القديمة.
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return auth()->user()
            ->orders()
            ->with('items')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);
    }
};
?>

{{-- قائمة طلبات العميل: رقم الطلب والتاريخ والحالة وملخص الأصناف والإجمالي، وزر التفاصيل. --}}
{{-- المبالغ تُعرض بعملة الطلب نفسه وقت الشراء. --}}
<x-store.account-shell heading="طلباتي">
    <ul class="space-y-4">
        @forelse ($this->orders as $order)
            @php
                $names = $order->items->pluck('product_name');
                $more = max(0, $names->count() - 2);
            @endphp

            <li wire:key="order-{{ $order->id }}" class="rounded-lg border border-line p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <a href="{{ route('account.orders.show', ['number' => $order->order_number]) }}" wire:navigate
                       class="font-extrabold text-brand-700 hover:underline" dir="ltr">{{ $order->order_number }}</a>
                    <x-store.order-badge :status="$order->status" />
                </div>

                <p class="mt-1 text-xs text-ink-soft">{{ $order->created_at->translatedFormat('j F Y، h:i A') }}</p>

                <p class="mt-3 text-sm">
                    {{ $names->take(2)->implode('، ') }}@if ($more > 0) <span class="text-ink-soft">و{{ $more }} أخرى</span>@endif
                </p>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="font-extrabold">
                        {{ \App\Support\Money::format($order->total, $order->currency_code ?: 'YER') }}
                    </p>
                    <a href="{{ route('account.orders.show', ['number' => $order->order_number]) }}" wire:navigate
                       class="rounded-md border border-line px-4 py-2 text-sm font-bold hover:bg-surface">التفاصيل</a>
                </div>
            </li>
        @empty
            <li class="rounded-lg border border-dashed border-line p-10 text-center">
                <p class="text-ink-soft">لا توجد طلبات في حسابك بعد.</p>
                <a href="{{ route('products.index') }}" wire:navigate
                   class="mt-4 inline-block rounded-md bg-brand-600 px-5 py-2.5 font-extrabold text-white hover:bg-brand-700">تصفّح المنتجات</a>
            </li>
        @endforelse
    </ul>

    <div class="mt-6">{{ $this->orders->links() }}</div>
</x-store.account-shell>