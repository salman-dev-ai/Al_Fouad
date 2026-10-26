<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\StoreSetting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->user()?->is_admin === true;
    }

    protected function getStats(): array
    {
        $currency = StoreSetting::current()?->currency_code ?? 'YER';

        // الحالات التي تُحسب مبيعات فعلية
        $salesStatuses = array_map(
            fn (OrderStatus $status): string => $status->value,
            [OrderStatus::Confirmed, OrderStatus::Shipped, OrderStatus::Delivered],
        );

        $salesToday = Order::query()
            ->whereIn('status', $salesStatuses)
            ->where('created_at', '>=', today())
            ->sum('total');

        $salesMonth = Order::query()
            ->whereIn('status', $salesStatuses)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total');

        $ordersToday = Order::query()
            ->where('created_at', '>=', today())
            ->count();

        $pending = Order::query()
            ->where('status', OrderStatus::Pending->value)
            ->count();

        return [
            Stat::make('مبيعات اليوم', $this->money($salesToday, $currency))
                ->description('آخر 7 أيام')
                ->chart($this->dailySales($salesStatuses))
                ->color('success'),

            Stat::make('مبيعات هذا الشهر', $this->money($salesMonth, $currency))
                ->description('مؤكدة وقيد التوصيل ومسلّمة')
                ->color('success'),

            Stat::make('طلبات اليوم', (string) $ordersToday)
                ->description('بكل الحالات')
                ->color('info'),

            Stat::make('تنتظر المراجعة', (string) $pending)
                ->description($pending > 0 ? 'تحتاج تأكيدًا' : 'لا توجد طلبات معلّقة')
                ->descriptionIcon($pending > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pending > 0 ? 'warning' : 'gray'),
        ];
    }

    /** للعرض فقط، المبالغ تُحسب في قاعدة البيانات لا هنا. */
    private function money(int|float|string|null $amount, string $currency): string
    {
        return number_format((float) $amount, 2) . ' ' . $currency;
    }

    /**
     * مجموع مبيعات كل يوم من آخر 7 أيام (للرسم البياني الصغير).
     *
     * @param  array<int, string>  $statuses
     * @return array<int, float>
     */
    private function dailySales(array $statuses): array
    {
        $from = today()->subDays(6);

        $rows = Order::query()
            ->whereIn('status', $statuses)
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, 6))
            ->map(fn (int $i): float => (float) ($rows[$from->copy()->addDays($i)->toDateString()] ?? 0))
            ->all();
    }
}
