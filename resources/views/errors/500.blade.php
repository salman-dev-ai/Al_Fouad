@extends('errors.layout')
@section('title', 'خطأ في الخادم')
@section('code', '500')
@section('heading', 'حدث خطأ من جهتنا')
@section('message', 'نعمل على إصلاحه. حاول مرة أخرى بعد قليل.')
@section('actions')
    <a href="{{ url()->current() }}" class="rounded-md bg-brand-600 px-6 py-3 font-extrabold text-white hover:bg-brand-700">إعادة المحاولة</a>
    <a href="{{ url('/') }}" class="rounded-md border border-brand-600 px-6 py-3 font-bold text-brand-700 hover:bg-brand-50">الصفحة الرئيسية</a>
@endsection