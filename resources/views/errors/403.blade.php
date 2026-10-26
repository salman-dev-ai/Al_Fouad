@extends('errors.layout')
@section('title', 'غير مسموح')
@section('code', '403')
@section('heading', 'ليست لديك صلاحية الوصول')
@section('message', 'هذه الصفحة غير متاحة لحسابك.')
@section('actions')
    <a href="{{ url('/') }}" class="rounded-md bg-brand-600 px-6 py-3 font-extrabold text-white hover:bg-brand-700">الصفحة الرئيسية</a>
@endsection