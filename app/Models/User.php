<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * @use HasFactory<UserFactory>
 *
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $google_id
 * @property string|null $avatar_url
 * @property UserStatus|string $status
 * @property string $member_code
 * @property string|null $document_type
 * @property string|null $document_number
 * @property string|null $document_hash
 * @property string|null $phone
 * @property Carbon|null $identity_verified_at
 * @property int|null $identity_verified_by_id
 * @property Carbon|null $blocked_until
 * @property string|null $suspension_reason
 * @property string $locale
 * @property Carbon|null $deleted_at
 */
class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'name',
        'email',
        'email_verified_at',
        'password',
        'google_id',
        'avatar_url',
        'status',
        'member_code',
        'document_type',
        'document_number',
        'document_hash',
        'phone',
        'identity_verified_at',
        'identity_verified_by_id',
        'blocked_until',
        'suspension_reason',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'document_number',
        'document_hash',
        'phone',
        'google_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'identity_verified_at' => 'datetime',
        'blocked_until' => 'datetime',
        'document_number' => 'encrypted',
        'phone' => 'encrypted',
        'status' => UserStatus::class,
        'deleted_at' => 'datetime',
    ];

    /** @return HasMany<Loan, $this> */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'user_id');
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
