<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;

// صفحة قائمة حسابات العملاء، بلا أزرار إنشاء.
// كل المنطق في الجدول والإجراء.
class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}