                @guest
                    <p class="rounded-lg bg-brand-50 p-4 text-sm">
                        لديك حساب؟
                        <a href="{{ route('login', ['next' => 'checkout']) }}" wire:navigate class="font-bold text-brand-700 underline underline-offset-4">سجّل دخولك</a>
                        أو
                        <a href="{{ route('register', ['next' => 'checkout']) }}" wire:navigate class="font-bold text-brand-700 underline underline-offset-4">أنشئ حسابًا</a>
                        لتُملأ بياناتك تلقائيًا في كل طلب.
                    </p>
                @endguest

                @if ($this->addresses->isNotEmpty())
                    <fieldset class="space-y-3 rounded-lg border border-line p-5">
                        <legend class="px-1 text-lg font-extrabold">عنوان الشحن</legend>

                        @foreach ($this->addresses as $address)
                            <label wire:key="choice-{{ $address->id }}" class="block cursor-pointer">
                                <input type="radio" wire:model.live="addressChoice" value="{{ $address->id }}" class="peer sr-only">
                                <span class="block rounded-md border border-line p-4 text-sm peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">
                                    <span class="font-bold">
                                        {{ $address->label ?: 'عنوان' }}
                                        @if ($address->is_default)
                                            <span class="ms-1 rounded-sm bg-brand-700 px-1.5 py-0.5 text-xs text-white">افتراضي</span>
                                        @endif
                                    </span>
                                    <span class="block text-ink-soft">{{ $address->recipient_name }} — {{ $address->city }}، {{ $address->address_line }}</span>
                                </span>
                            </label>
                        @endforeach

                        <label class="block cursor-pointer">
                            <input type="radio" wire:model.live="addressChoice" value="" class="peer sr-only">
                            <span class="block rounded-md border border-line p-4 text-sm font-bold peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">
                                + عنوان آخر
                            </span>
                        </label>
                    </fieldset>
                @endif
