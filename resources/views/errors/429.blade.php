@extends('errors.layout')
@section('title', 'محاولات كثيرة')
@section('code', '429')
@section('heading', 'طلبات كثيرة خلال وقت قصير')
@section('message', 'انتظر قليلًا ثم حاول مرة أخرى.')
@section('actions')
    <a href="{{ url('/') }}" class="rounded-md bg-brand-600 px-6 py-3 font-extrabold text-white hover:bg-brand-700">الصفحة الرئيسية</a>
@endsection