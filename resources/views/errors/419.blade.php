@extends('errors.layout')
@section('title', 'انتهت الجلسة')
@section('code', '419')
@section('heading', 'انتهت صلاحية الصفحة')
@section('message', 'أعد تحميل الصفحة وحاول مرة أخرى.')
@section('actions')
    <a href="{{ url()->current() }}" class="rounded-md bg-brand-600 px-6 py-3 font-extrabold text-white hover:bg-brand-700">إعادة التحميل</a>
@endsection