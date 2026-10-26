@extends('errors.layout')
@section('title', 'الصفحة غير موجودة')
@section('code', '404')
@section('heading', 'لم نجد هذه الصفحة')
@section('message', 'قد يكون الرابط خاطئًا أو أن الصفحة لم تعد موجودة.')
@section('actions')
    <a href="{{ url('/') }}" class="rounded-md bg-brand-600 px-6 py-3 font-extrabold text-white hover:bg-brand-700">الصفحة الرئيسية</a>
    <a href="{{ url('/products') }}" class="rounded-md border border-brand-600 px-6 py-3 font-bold text-brand-700 hover:bg-brand-50">تصفح المنتجات</a>
@endsection