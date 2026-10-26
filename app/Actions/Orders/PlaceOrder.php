<?php

namespace App\Actions\Orders;


use App\Models\StoreSetting;
use App\Actions\Cart\SummarizeCart;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    public function __construct(private readonly SummarizeCart $summarize) {}

    /**
     * @param  array{name: string, phone: string, email: ?string, city: string, address: string, notes: ?string, location_url: ?string}  $data
     *         بيانات مُتحقَّق منها، والهاتف بصيغة E.164
     *
     * @throws ValidationException
     */
    public function execute(Cart $cart, array $data): Order
    {

        if (StoreSetting::current()?->maintenance_mode) {
    throw ValidationException::withMessages([
        'cart' => 'المتجر مغلق مؤقتًا للصيانة. حاول لاحقًا.',
    ]);
}
        return DB::transaction(function () use ($cart, $data): Order {
            // قفل السلة: ضغطتان متزامنتان على «تأكيد» تتسلسلان، والثانية تجد السلة فارغة
            Cart::query()->whereKey($cart->getKey())->lockForUpdate()->firstOrFail();

            $variantIds = CartItem::query()->where('cart_id', $cart->id)->pluck('product_variant_id')->all();

            if ($variantIds === []) {
                throw ValidationException::withMessages(['cart' => 'سلتك فارغة.']);
            }

            // قفل الخيارات بترتيب ثابت لمنع تعارض (deadlock) بين طلبين متزامنين
            ProductVariant::query()->whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->get();

            // إعادة حساب كل شيء من قاعدة البيانات بعد القفل، ولا شيء من المتصفح
            $summary = $this->summarize->execute($cart);

            if ($summary->isEmpty() || $summary->hasBlockingIssues()) {
                throw ValidationException::withMessages([
                    'cart' => 'بعض عناصر سلتك لم تعد متاحة بالكمية المطلوبة. راجع السلة ثم أعد المحاولة.',
                ]);
            }

            $customer = $this->resolveCustomer($data);

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $customer->id,
                'customer_name' => $data['name'],
                'customer_phone' => $data['phone'],
                'delivery_address' => $data['address'],
                'city' => $data['city'],
                'notes' => $data['notes'] ?: null,
                'location_url' => $data['location_url'] ?: null,
                'subtotal' => Money::fromMinor($summary->subtotalMinor),
                'tax_rate' => $summary->taxRate,
                'tax_amount' => Money::fromMinor($summary->taxMinor),
                'delivery_fee' => Money::fromMinor($summary->deliveryFeeMinor),
                'total' => Money::fromMinor($summary->totalMinor),
                'payment_method' => PaymentMethod::CashOnDelivery,
                'status' => OrderStatus::Pending,
            ]);

            foreach ($summary->lines as $line) {
                $variant = $line['variant'];

                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'unit_price' => Money::fromMinor($line['unit_minor']),
                    'quantity' => $line['quantity'],
                    'line_total' => Money::fromMinor($line['line_minor']),
                ]);

                // خصم ذرّي مشروط: لا يمكن أن يصبح المخزون سالبًا حتى لو تعطلت كل الفحوص السابقة
                $affected = ProductVariant::query()
                    ->whereKey($variant->id)
                    ->where('stock_quantity', '>=', $line['quantity'])
                    ->decrement('stock_quantity', $line['quantity']);

                if ($affected !== 1) {
                    throw ValidationException::withMessages(['cart' => 'نفد مخزون أحد العناصر للتو. راجع سلتك.']);
                }
            }

            $cart->items()->delete();

            return $order;
        });
    }

    /**
     * العميل يُعرَّف برقم هاتفه الموحّد فقط (لا دمج بالاسم أبدًا).
     * لا نغيّر اسم عميل موجود لأن شخصًا قد يكتب رقم غيره؛ الطلب نفسه يحفظ الاسم المُدخل.
     */
    private function resolveCustomer(array $data): Customer
    {
        $customer = Customer::query()->where('phone', $data['phone'])->lockForUpdate()->first();

        if ($customer) {
            if (blank($customer->email) && filled($data['email'] ?? null)) {
                $customer->update(['email' => $data['email']]);
            }

            return $customer;
        }

        try {
            return Customer::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?: null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // طلب متزامن أنشأه قبلنا بلحظة
            return Customer::query()->where('phone', $data['phone'])->firstOrFail();
        }
    }

    /** رقم عشوائي غير قابل للتخمين، مثل FD-7K3M9Q (بلا أحرف متشابهة: 0 O 1 I). */
    private function generateOrderNumber(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $prefix = config('store.order_prefix', 'FD');

        do {
            $suffix = '';

            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $number = "{$prefix}-{$suffix}";
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
