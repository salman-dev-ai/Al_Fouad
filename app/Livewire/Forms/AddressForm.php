<?php

namespace App\Livewire\Forms;

use App\Models\Address;
use App\Models\User;
use App\Support\GoogleMapsUrl;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Form;

// AddressForm.php

// نموذج العنوان القابل لإعادة الاستخدام (صفحة العناوين الآن، وإتمام الطلب لاحقًا).
// يحوي الحقول والتحقق والحفظ، فتبقى الصفحات نحيفة.
// معرّف العنوان المعدَّل مقفل (Locked) فلا يستطيع المتصفح تغييره.
class AddressForm extends Form
{
    #[Locked]
    public ?int $addressId = null;

    public string $label = '';
    #[Validate]
    public string $recipient_name = '';
    #[Validate]
    public string $phone = '';
    #[Validate]
    public string $city = '';
    #[Validate]
    public string $address_line = '';
    #[Validate]
    public string $notes = '';
    #[Validate]
    public string $location_url = '';

    public bool $is_default = false;

    // قواعد التحقق: الجوال يُوحَّد ويُرفض غير الصالح، ورابط الخريطة يقبل خرائط Google عبر https فقط.
    // كل حقل نصي له حد أقصى يطابق عمود قاعدة البيانات.
    // تُنفَّذ عند استدعاء validate() فقط.
    protected function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail): void {
                if (PhoneNumber::normalize((string) $value, config('store.default_country_code')) === null) {
                    $fail('أدخل رقم جوال صحيحًا، مثل 777123456.');
                }
            }],
            'city' => ['required', 'string', 'max:100'],
            'address_line' => ['required', 'string', 'min:5', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'location_url' => ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, \Closure $fail): void {
                if (filled($value) && ! GoogleMapsUrl::isValid((string) $value)) {
                    $fail('أدخل رابطًا صحيحًا من خرائط Google، أو استخدم زر «استخدم موقعي الحالي».');
                }
            }],
            'is_default' => ['boolean'],
        ];
    }

    // رسائل الأخطاء بالعربية.
    // تُستعمل مع أسماء الحقول المعرّفة في validationAttributes().
    protected function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'min' => 'حقل :attribute قصير جدًا.',
            'max' => 'حقل :attribute طويل جدًا.',
        ];
    }

    // أسماء الحقول كما تظهر للعميل في رسائل الأخطاء.
    protected function validationAttributes(): array
    {
        return [
            'label' => 'اسم العنوان',
            'recipient_name' => 'اسم المستلم',
            'phone' => 'رقم الجوال',
            'city' => 'المدينة',
            'address_line' => 'العنوان',
            'notes' => 'الملاحظات',
            'location_url' => 'رابط الموقع',
        ];
    }

    // يملأ النموذج من عنوان موجود للتعديل، ويحفظ معرّفه (المقفل) لاستخدامه عند الحفظ.
    // يُستدعى بعد جلب العنوان من عناوين المستخدم نفسه فقط.
    // القيم الفارغة تتحول لنص فارغ ليناسب حقول النموذج.
    public function setAddress(Address $address): void
    {
        $this->addressId = $address->id;
        $this->label = (string) $address->label;
        $this->recipient_name = $address->recipient_name;
        $this->phone = $address->phone;
        $this->city = $address->city;
        $this->address_line = $address->address_line;
        $this->notes = (string) $address->notes;
        $this->location_url = (string) $address->location_url;
        $this->is_default = $address->is_default;
    }

    // يتحقق ثم ينشئ عنوانًا جديدًا أو يعدّل عنوانًا يملكه المستخدم (لا غيره) داخل معاملة.
    // أول عنوان للمستخدم يصبح افتراضيًا تلقائيًا، ومن يطلب «افتراضي» يُلغى الافتراضي القديم.
    // يعيد العنوان المحفوظ.
    public function save(User $user): Address
    {
        $this->validate();

        $data = [
            'label' => filled($this->label) ? trim($this->label) : null,
            'recipient_name' => trim($this->recipient_name),
            'phone' => PhoneNumber::normalize($this->phone, config('store.default_country_code')),
            'city' => trim($this->city),
            'address_line' => trim($this->address_line),
            'notes' => filled($this->notes) ? trim($this->notes) : null,
            'location_url' => filled($this->location_url) ? trim($this->location_url) : null,
        ];

        return DB::transaction(function () use ($user, $data): Address {
            if ($this->addressId) {
                $address = $user->addresses()->lockForUpdate()->findOrFail($this->addressId);
                $address->update($data);
            } else {
                $address = $user->addresses()->create($data);
            }

            if ($this->is_default || ! $user->addresses()->where('is_default', true)->exists()) {
                $address->makeDefault();
            }

            return $address;
        });
    }
}