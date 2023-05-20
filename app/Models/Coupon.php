<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class Coupon extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logOnly(['*'])
            ->dontSubmitEmptyLogs();
    }

    /**
     * @var string[]
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'uses',
        'max_uses',
        'expires_at'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'value' => 'float',
        'uses' => 'integer',
        'max_uses' => 'integer',
        'expires_at' => 'timestamp'
    ];

    /**
     * Returns the date format used by the coupons.
     *
     * @return string
     */
    public static function formatDate(): string
    {
        return 'Y-MM-DD HH:mm:ss';
    }

    /**
     * Returns the current state of the coupon.
     *
     * @return string
     */
    public function getStatus()
    {
        if ($this->uses >= $this->max_uses) {
            return 'USES_LIMIT_REACHED';
        }

        if (!is_null($this->expires_at)) {
            if ($this->expires_at <= Carbon::now(config('app.timezone'))->timestamp) {
                return __('EXPIRED');
            }
        }

        return __('VALID');
    }

    /**
     * Check if a user has already exceeded the uses of a coupon.
     *
     * @param Request $request The request being made.
     * @param CouponSettings $coupon_settings The instance of the coupon settings.
     *
     * @return bool
     */
    public function isLimitsUsesReached($requestUser, $coupon_settings): bool
    {
        $coupon_uses = $requestUser->coupons()->where('id', $this->id)->count();

        return $coupon_uses >= $coupon_settings->max_uses_per_user ? true : false;
    }

    /**
     * Generate a specified quantity of coupon codes.
     *
     * @param int $amount Amount of coupons to be generated.
     *
     * @return array
     */
    public static function generateRandomCoupon(int $amount = 10): array
    {
        $coupons = [];

        for ($i = 0; $i < $amount; $i++) {
            $random_coupon = strtoupper(bin2hex(random_bytes(3)));

            $coupons[] = $random_coupon;
        }

        return $coupons;
    }

    /**
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_coupons');
    }
}