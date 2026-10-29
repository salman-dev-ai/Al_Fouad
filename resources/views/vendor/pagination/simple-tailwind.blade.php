@if ($paginator->hasPages())
    <nav role="navigation" aria-label="التنقل بين صفحات المنتجات"
         class="flex items-center justify-end gap-3 py-4"
         dir="rtl">

        {{-- السابق --}}
        @if ($paginator->onFirstPage())
            <span class="cursor-not-allowed rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-400">
                السابق
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"
               rel="prev"
               class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-400 hover:bg-gray-50">
                السابق
            </a>
        @endif

        {{-- التالي --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"
               rel="next"
               class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700">
                التالي
            </a>
        @else
            <span class="cursor-not-allowed rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-400">
                التالي
            </span>
        @endif

    </nav>
@endif
