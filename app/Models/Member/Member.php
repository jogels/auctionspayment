<?php

namespace App\Models\Member;

use App\Models\Notification;
use App\Models\Payments\Payment;
use App\Models\Product\Transaction;
use App\Models\Product\Wishlist;
use App\Models\Store\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $appends = ['image_url', 'full_name', 'default_address', 'has_store'];

    public function getImageUrlAttribute()
    {
        if (str_contains($this->image, 'http')) {
            return $this->image;
        }

        return !empty($this->image)
            ? url('images/members/' . $this->image)
            : url('images/placeholder/no-user.png');
    }

    public function getFullNameAttribute()
    {
        $name = $this->first_name ? $this->first_name . ' ' : null;
        $name .= $this->middle_name ? $this->middle_name . ' ' : null;
        $name .= $this->last_name ? $this->last_name : null;

        return $name;
    }

    public function getDefaultAddressAttribute()
    {
        return MemberAddress::where('user_id', $this->user_id)->whereIsDefault(true)->first();
    }

    public function getHasStoreAttribute()
    {
        $store = Store::where('user_id', $this->user_id)->first();

        if ($store) {
            return true;
        } else {
            return false;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function addresses()
    {
        return $this->hasMany(MemberAddress::class, 'user_id', 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'member_id', 'id');
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class, 'member_id', 'id');
    }

    public function payment()
    {
        return $this->hasMany(Payment::class, 'member_id', 'id');
    }

    public function notification()
    {
        return $this->hasMany(Notification::class, 'member_id', 'id');
    }
}
