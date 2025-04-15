<?php

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\DB;

/**
 * Class User
 *
 * @property string $name
 * @property string $email
 * @property string $password
 * @property Role[] $roles
 *
 * @method static User create(array $user)
 * @package App
 */
/**
 * @method static \Illuminate\Database\Eloquent\Builder|User donors()
 * @method static \Illuminate\Database\Eloquent\Builder|User foodbanks()
 * @method static \Illuminate\Database\Eloquent\Builder|User recipients()
 * @method static \Illuminate\Database\Eloquent\Builder|User admins()
 * @method static \Illuminate\Database\Eloquent\Builder|User pendingFoodbanks()
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use HasRoles;
    use Filterable;

    /**
     * Set permissions guard to API by default
     * @var string
     */
    protected $guard_name = 'api';

    /**
     * Sex map
     */
    public const SEXMAP = [
    'M' => 'Male',
    'F' => 'Female'
    ];


    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'birthday'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'email', 'password', 'status', 'sex', 'birthday', 'description',
        'phone', 'role', 'profile_picture', 'location', 'address',
        'organization_name', 'recipient_type', 'donor_type', 'notes'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    public $appends = ['age', 'sex_format'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'updated_at',
        'deleted_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the avatar and return the default avatar if the avatar is null.
     *
     * @param string $value
     * @return string
     */
    public function getAvatarAttribute($value)
    {
        return !empty($value) ? $value : config('content.default_avatar');
    }

    public function getSexFormatAttribute()
    {
        return self::SEXMAP[$this->sex] ?? 'Unknown';
    }

    public function getAgeAttribute()
    {
        if (empty($this->birthday)) {
            return 0;
        }
        return Carbon::now()->diffInYears($this->birthday);
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role || $this->hasPermissionTo($role);
    }

    /**
     * Check if user has a permission.
     *
     * @param String $permission
     * @return bool
     */
    public function hasPermission($permission): bool
    {
        // Loop through each of the roles associated with the user
        foreach ($this->roles as $role) {
            // Check if any permission within the role matches the provided permission
            if ($role->hasPermissionTo($permission)) {
                return true;
            }
        }
        return false;
    }

       // Assuming a user can have multiple donation requests
    public function donationRequests()
    {
        return $this->hasMany(DonationRequest::class, 'foodbank_id'); // Adjust foreign key if needed
    }

    public function donations()
    {
        return $this->hasMany(Donation::class, 'foodbank_id'); // Adjust foreign key if needed
    }

    public function requestsFb()
    {
         return $this->hasMany(RequestFB::class, 'recipient_id');
    }


    /**
     * Check if the user is an Admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->roles()->where('name', 'admin')->exists();
    }

    /**
     * Scope a query to only include users of a given role.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope a query to only include donors.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDonors($query)
    {
        return $query->where('role', 'donor');
    }

    /**
     * Scope a query to only include foodbanks.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFoodbanks($query)
    {
        return $query->where('role', 'foodbank');
    }

    /**
     * Scope a query to only include recipients.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecipients($query)
    {
        return $query->where('role', 'recipient');
    }

    /**
     * Scope a query to only include admins.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopePendingFoodbanks($query)
    {
        return $query->where('role', 'foodbank')->where('status', 'pending');
    }

    public function isDonor(): bool
    {
        return $this->role === 'donor';
    }

    public function isFoodbank(): bool
    {
        return $this->role === 'foodbank';
    }

    public function isRecipient(): bool
    {
        return $this->role === 'recipient';
    }

    // In User model
    public static function getHistoricalData($role, $period)
    {
        return self::where('role', $role)
                ->where('created_at', '>=', now()->subDays($period))
                ->groupBy('date')
                ->orderBy('date')
                ->get([
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count'),
                ]);
    }

        public function foodbankActivities()
    {
        return $this->hasMany(FoodbankActivity::class, 'foodbank_id');
    }

    public function donorTransactions()
    {
        return $this->hasMany(DonorTransaction::class, 'donor_id');
    }
}
