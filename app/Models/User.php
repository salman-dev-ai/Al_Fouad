<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'email', 'password','phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */


    public function canAccessPanel(Panel $panel): bool
{
    return $this->is_admin;
}

// عناوين الشحن المحفوظة للمستخدم.
// كل عمليات العناوين في المتجر تمر من هذه العلاقة، فتكون محصورة بعناوينه فقط.
public function addresses(): HasMany
{
    return $this->hasMany(Address::class);
}


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    // طلبات هذا الحساب، وهي ما أُجري وهو مسجّل دخوله فقط.
// لا تتضمن طلبات الزائر القديمة بالرقم نفسه (حفاظًا على الخصوصية كما اتفقنا).
public function orders(): HasMany
{
    return $this->hasMany(Order::class);
}


// المنتجات المفضلة لهذا الحساب (عبر جدول favorites)، مع تاريخ الإضافة.
// تُستعمل لعرض صفحة المفضلة ولحفظ/حذف المفضلة من مكوّن المزامنة.
public function favoriteProducts(): BelongsToMany
{
    return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
}
}
