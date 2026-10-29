<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LogoutController;


Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/products', 'pages::products')->name('products.index');
Route::livewire('/products/{product:slug}', 'pages::product')->name('products.show');
Route::livewire('/cart', 'pages::cart')->name('cart');
Route::livewire('/checkout', 'pages::checkout')->name('checkout');
Route::livewire('/orders/{order:order_number}/confirmed', 'pages::order-confirmed')->name('order.confirmed');
// صفحات الدخول والتسجيل للضيوف فقط، وحساب العميل للمسجّلين فقط.
// من يزور /account بلا دخول يُحوَّل إلى /login ثم يعود إليها بعد نجاح الدخول.
// الخروج بطريقة POST محمية بـ CSRF.
Route::livewire('/login', 'pages::login')->middleware('guest')->name('login');
Route::livewire('/register', 'pages::register')->middleware('guest')->name('register');
Route::livewire('/account', 'pages::account')->middleware('auth')->name('account');
Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

// صفحتا الملف الشخصي وكلمة المرور، للمسجّلين فقط.
// تستعملان الإطار المشترك للحساب (account-shell).
// أي قسم جديد يُضاف هنا بالنمط نفسه.
Route::livewire('/account/profile', 'pages::account-profile')->middleware('auth')->name('account.profile');
Route::livewire('/account/password', 'pages::account-password')->middleware('auth')->name('account.password');

// صفحة عناوين الشحن للمسجّلين فقط.
// تستعمل إطار الحساب المشترك وكلاس AddressForm.
Route::livewire('/account/addresses', 'pages::account-addresses')->middleware('auth')->name('account.addresses');

// طلبات العميل: القائمة وتفاصيل طلب بعينه، للمسجّلين فقط.
// رقم الطلب في الرابط يُقيَّد بحروف وأرقام وشرطة، ويُبحث عنه ضمن طلبات المستخدم فقط.
// أي رقم لا يخصه يعطي 404.
Route::livewire('/account/orders', 'pages::account-orders')->middleware('auth')->name('account.orders');
Route::livewire('/account/orders/{number}', 'pages::account-order')
    ->middleware('auth')
    ->where('number', '[A-Za-z0-9\-]+')
    ->name('account.orders.show');

    // صفحة المفضلة عامة: المسجّل يرى مفضلة حسابه، والزائر يرى مفضلة متصفحه.
Route::livewire('/favorites', 'pages::favorites')->name('favorites');




