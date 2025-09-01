<?php
/*
 * File name: Purchase.php
 * Last modified: 2024.04.18 at 17:53:44
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use App\Casts\BookingCast;
use App\Casts\EServiceCollectionCast;
use App\Casts\OptionCollectionCast;
use App\Casts\TaxCollectionCast;
use App\Events\PurchaseCreatingEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;


/**
 * Class Purchase
 * @package App\Models
 * @version January 25, 2021, 9:22 pm UTC
 *
 * @property int id
 * @property User user
 * @property PurchaseStatus purchaseStatus
 * @property Payment payment
 * @property Salon salon
 * @property Booking booking
 * @property EService[] e_services
 * @property integer quantity
 * @property integer user_id
 * @property integer purchase_status_id
 * @property integer payment_status_id
 * @property integer payment_id
 * @property boolean at_salon
 * @property Coupon coupon
 * @property Tax[] taxes
 * @property \DateTime purchase_at
 * @property string hint
 * @property boolean cancel
 */
class Purchase extends Model
{
   
    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'user_id' => 'required|exists:users,id',
        'purchase_status_id' => 'required|exists:purchase_statuses,id',
        'payment_id' => 'nullable|exists:payments,id'
    ];
    public $table = 'purchases';
    protected $attributes = [
        'booking' => null,   // valeur par défaut si rien n’est défini
       
    ];
    public $fillable = [
        'salon',
        'booking',
        'e_services',
        'quantity',
        'user_id',
        'purchase_status_id',
        'payment_id',
        'coupon',
        'taxes',
        'purchase_at',
        'cancel'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'salon' => Salon::class,
        'booking' => BookingCast::class,
        'e_services' => EServiceCollectionCast::class,
        'coupon' => Coupon::class,
        'taxes' => TaxCollectionCast::class,
        'purchase_status_id' => 'integer',
        'payment_id' => 'integer',
        'quantity' => 'integer',
        'user_id' => 'integer',
        'purchase_at' => 'datetime:Y-m-d\TH:i:s.uP',
        'hint' => 'string',
        'cancel' => 'boolean'
    ];
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields',
        // 'at_salon',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'creating' => PurchaseCreatingEvent::class,
        'updating' => PurchaseCreatingEvent::class,
    ];

    public function getCustomFieldsAttribute(): array
    {
        $hasCustomField = in_array(static::class, setting('custom_field_models', []));
        if (!$hasCustomField) {
            return [];
        }
        $array = $this->customFieldsValues()
            ->join('custom_fields', 'custom_fields.id', '=', 'custom_field_values.custom_field_id')
            ->where('custom_fields.in_table', '=', true)
            ->get()->toArray();

        return convertToAssoc($array, 'name');
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['total'] = $this->getTotal();
        $array['sub_total'] = $this->getSubtotal();
        $array['taxes_value'] = $this->getTaxesValue();
        return $array;
    }

    public function getTotal(): float
    {
        $total = $this->getSubtotal();
        $total += $this->getTaxesValue();
        $total -= $this->getCouponValue();
        return $total;
    }

    public function getSubtotal(): float
    {
        $total = 0;
        foreach ($this->e_services as $eService) {
            $total += $eService->getPrice() * ($this->quantity >= 1 ? $this->quantity : 1);
        }
        // foreach ($this->options as $option) {
        //     $total += $option->price * ($this->quantity >= 1 ? $this->quantity : 1);
        // }
        return $total;
    }

    public function getTaxesValue(): float
    {
        $total = $this->getSubtotal();
        $taxValue = 0;
        foreach ($this->taxes as $tax) {
            if ($tax->type == 'percent') {
                $taxValue += ($total * $tax->value / 100);
            } else {
                $taxValue += $tax->value;
            }
        }
        return $taxValue;
    }

    public function getCouponValue(): float|int|null
    {
        return $this->coupon->value;
    }

    public function customFieldsValues(): MorphMany
    {
        return $this->morphMany('App\Models\CustomFieldValue', 'customizable');
    }


    // public function getAtSalonAttribute(): bool
    // {
    //     return $this->address->id == ( is_null($this->salon->address)? $this->salon->address_id : $this->salon->address->id ) ;
    // }

    /**
     * @return BelongsTo
     **/
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

  

    /**
     * @return BelongsTo
     **/
    public function purchaseStatus(): BelongsTo
    {
        return $this->belongsTo(PurchaseStatus::class, 'purchase_status_id', 'id');
    }

    /**
     * @return BelongsTo
     **/
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'id');
    }

}

