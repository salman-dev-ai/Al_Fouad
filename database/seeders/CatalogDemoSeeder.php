<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * بيانات عرض تجريبية للتطوير فقط (بدون صور).
 * آمن للتكرار: لا يكرّر السجلات ولا يعيد كتابة أسعار أو مخزون عدّلته بنفسك.
 */
class CatalogDemoSeeder extends Seeder
{
    /** مقاسات الحجم الكبير يُضاف لسعرها هذا المبلغ. */
    private const PLUS_SIZES = ['XL', 'XXL', '3XL'];

    private const PLUS_EXTRA = 1000;

    /** دورة مخزون ثابتة تشمل كميات منخفضة وصفرية لاختبار لوحة المؤشرات. */
    private const STOCK_CYCLE = [12, 8, 5, 3, 0, 10, 6, 2, 15, 9, 7, 4];

    private int $stockIndex = 0;

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('هذا الـ Seeder للتطوير فقط ولا يعمل في بيئة الإنتاج.');

            return;
        }

        DB::transaction(function (): void {
            $categoryIds = $this->seedCategories();
            $this->seedProducts($categoryIds);
        });

        $this->command?->info(sprintf(
            'تم: %d تصنيفات، %d منتجات، %d خيارات.',
            Category::count(),
            Product::count(),
            ProductVariant::count(),
        ));
    }

    /** @return array<string, int> slug => id */
    private function seedCategories(): array
    {
        $categories = [
            'shirts' => ['قمصان', 'قمصان قطن وكتان بقصّات عصرية لكل المناسبات.'],
            'pants' => ['بناطيل', 'بناطيل جينز وقماش ورياضية بمقاسات متعددة.'],
            'men' => ['ملابس رجالية', 'ثياب وأطقم وملابس رجالية متنوعة.'],
            'women' => ['ملابس نسائية', 'عبايات وفساتين وحجابات بتصاميم أنيقة.'],
            'kids' => ['ملابس الأطفال', 'ملابس مريحة وآمنة للأطفال.'],
        ];

        $ids = [];

        foreach ($categories as $slug => [$name, $description]) {
            $ids[$slug] = Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $description, 'is_active' => true],
            )->id;
        }

        return $ids;
    }

    /** @param array<string, int> $categoryIds */
    private function seedProducts(array $categoryIds): void
    {
        foreach ($this->catalog() as $item) {
            $product = Product::firstOrCreate(
                ['slug' => $item['slug']],
                [
                    'category_id' => $categoryIds[$item['category']],
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'is_featured' => $item['featured'] ?? false,
                    'is_active' => true,
                ],
            );

            foreach ($item['colors'] as $colorIndex => $color) {
                foreach ($item['sizes'] as $size) {
                    $price = $item['price'] + (in_array($size, self::PLUS_SIZES, true) ? self::PLUS_EXTRA : 0);

                    ProductVariant::firstOrCreate(
                        ['product_id' => $product->id, 'size' => $size, 'color' => $color],
                        [
                            'price' => $price,
                            'compare_at_price' => isset($item['was_extra']) ? $price + $item['was_extra'] : null,
                            'stock_quantity' => self::STOCK_CYCLE[$this->stockIndex++ % count(self::STOCK_CYCLE)],
                            'sku' => sprintf('%s-%s-C%d', $item['code'], str_replace(' ', '', $size), $colorIndex + 1),
                        ],
                    );
                }
            }
        }
    }

    /**
     * الأسعار بالريال اليمني كأمثلة فقط؛ عدّلها من لوحة الإدارة.
     * المقاسات من القائمة المعتمدة في نموذج المنتج بلوحة الإدارة.
     *
     * @return array<int, array<string, mixed>>
     */
    private function catalog(): array
    {
        return [
            [
                'code' => 'SH01', 'slug' => 'classic-cotton-shirt', 'category' => 'shirts', 'featured' => true,
                'name' => 'قميص قطن كلاسيكي',
                'description' => 'قميص من القطن الناعم بقصّة مريحة، مناسب للدوام والمناسبات.',
                'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['أبيض', 'أزرق سماوي'], 'price' => 12000,
            ],
            [
                'code' => 'SH02', 'slug' => 'summer-linen-shirt', 'category' => 'shirts',
                'name' => 'قميص كتان صيفي',
                'description' => 'كتان خفيف يمنح انتعاشًا في الأجواء الحارة.',
                'sizes' => ['M', 'L', 'XL', 'XXL'], 'colors' => ['بيج', 'أخضر زيتي'], 'price' => 14000, 'was_extra' => 3000,
            ],
            [
                'code' => 'SH03', 'slug' => 'casual-striped-shirt', 'category' => 'shirts',
                'name' => 'قميص مخطط كاجوال',
                'description' => 'قميص بخطوط ناعمة لإطلالة يومية عصرية.',
                'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['كحلي', 'رمادي'], 'price' => 11000,
            ],
            [
                'code' => 'PN01', 'slug' => 'slim-fit-jeans', 'category' => 'pants', 'featured' => true,
                'name' => 'بنطال جينز سليم',
                'description' => 'جينز مرن بقصّة سليم يدوم طويلًا.',
                'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['أزرق غامق', 'أسود'], 'price' => 18000,
            ],
            [
                'code' => 'PN02', 'slug' => 'formal-trousers', 'category' => 'pants',
                'name' => 'بنطال قماش رسمي',
                'description' => 'بنطال أنيق بقماش ثابت لا يتجعّد بسهولة.',
                'sizes' => ['M', 'L', 'XL', 'XXL'], 'colors' => ['أسود', 'رمادي فحمي'], 'price' => 20000,
            ],
            [
                'code' => 'PN03', 'slug' => 'jogger-sweatpants', 'category' => 'pants',
                'name' => 'بنطال رياضي',
                'description' => 'بنطال رياضي مريح للتمارين والاستخدام اليومي.',
                'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['رمادي', 'كحلي'], 'price' => 9000, 'was_extra' => 3000,
            ],
            [
                'code' => 'MN01', 'slug' => 'white-thobe', 'category' => 'men', 'featured' => true,
                'name' => 'ثوب رجالي أبيض',
                'description' => 'ثوب بقماش فاخر وتطريز ناعم عند الياقة.',
                'sizes' => ['M', 'L', 'XL', 'XXL'], 'colors' => ['أبيض', 'كريمي'], 'price' => 25000,
            ],
            [
                'code' => 'MN02', 'slug' => 'mens-tracksuit', 'category' => 'men',
                'name' => 'طقم رياضي رجالي',
                'description' => 'طقم من قطعتين بقماش قطني مريح.',
                'sizes' => ['M', 'L', 'XL'], 'colors' => ['أسود', 'كحلي'], 'price' => 22000,
            ],
            [
                'code' => 'WM01', 'slug' => 'embroidered-abaya', 'category' => 'women', 'featured' => true,
                'name' => 'عباية سوداء مطرزة',
                'description' => 'عباية بتطريز راقٍ على الأكمام وقماش منساب.',
                'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['أسود'], 'price' => 35000,
            ],
            [
                'code' => 'WM02', 'slug' => 'summer-dress', 'category' => 'women',
                'name' => 'فستان صيفي',
                'description' => 'فستان خفيف بألوان هادئة للمناسبات النهارية.',
                'sizes' => ['S', 'M', 'L'], 'colors' => ['وردي', 'أزرق', 'أخضر'], 'price' => 24000, 'was_extra' => 6000,
            ],
            [
                'code' => 'WM03', 'slug' => 'chiffon-hijab', 'category' => 'women',
                'name' => 'حجاب شيفون',
                'description' => 'شيفون ناعم وخفيف بخامة لا تنزلق بسهولة.',
                'sizes' => ['Free Size'], 'colors' => ['أسود', 'بيج', 'رمادي', 'كحلي'], 'price' => 4000,
            ],
            [
                'code' => 'KD01', 'slug' => 'kids-cotton-set', 'category' => 'kids',
                'name' => 'طقم أطفال قطن',
                'description' => 'طقم قطني ناعم على البشرة وسهل الغسيل.',
                'sizes' => ['S', 'M', 'L'], 'colors' => ['أزرق', 'وردي'], 'price' => 8000,
            ],
        ];
    }
}
