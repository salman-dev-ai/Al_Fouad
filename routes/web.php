<?php
use Illuminate\Support\Facades\Route;




Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/products', 'pages::products')->name('products.index');
Route::livewire('/products/{product:slug}', 'pages::product')->name('products.show');
Route::livewire('/cart', 'pages::cart')->name('cart');
Route::livewire('/checkout', 'pages::checkout')->name('checkout');
Route::livewire('/orders/{order:order_number}/confirmed', 'pages::order-confirmed')->name('order.confirmed');
