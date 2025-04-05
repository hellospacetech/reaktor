<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property string|null $short_name
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $fax
 * @property string|null $website
 * @property string|null $telex
 * @property string|null $eft_code
 * @property string|null $swift_code
 * @property string|null $logo_path
 * @property string $country_code
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Bank extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'short_name',
        'address',
        'phone',
        'fax',
        'website',
        'telex',
        'eft_code',
        'swift_code',
        'logo_path',
        'country_code',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Kullanıcı banka hesapları ilişkisi
     * 
     * @return HasMany<UserBankAccount>
     */
    public function userBankAccounts(): HasMany
    {
        return $this->hasMany(UserBankAccount::class);
    }
}
