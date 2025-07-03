<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // اضافه کردن BelongsTo برای رابطه با User

class LegalInfo extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'economic_code',
        'legal_national_id',
        'registration_number',
        'legal_phone',
        'province',
        'legal_city',
        'legal_address',
        'legal_postal_code',
    ];

    /**
     * Get the user that owns the legal information.
     * دریافت کاربری که این اطلاعات حقوقی به او تعلق دارد.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
