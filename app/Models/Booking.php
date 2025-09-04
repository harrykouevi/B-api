<?php
/*
 * File name: Booking.php
 * Last modified: 2024.04.18 at 17:53:44
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use Carbon\Carbon;
use App\Casts\TaxCollectionCast;
use App\Casts\OptionCollectionCast;
use App\Events\BookingCreatingEvent;
use App\Casts\EServiceCollectionCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Booking
 * @package App\Models
 * @version January 25, 2021, 9:22 pm UTC
 *
 * @property int id
 * @property User user
 * @property BookingStatus bookingStatus
 * @property Payment payment
 * @property Salon salon
 * @property EService[] e_services
 * @property Option[] options
 * @property integer quantity
 * @property integer user_id
 * @property integer address_id
 * @property integer booking_status_id
 * @property integer payment_status_id
 * @property Address address
 * @property integer payment_id
 * @property double duration
 * @property boolean at_salon
 * @property Coupon coupon
 * @property Tax[] taxes
 * @property Tax[] purchase_taxes
 * @property \DateTime booking_at
 * @property \DateTime start_at
 * @property \DateTime ends_at
 * @property string hint
 * @property boolean cancel
 */
class Booking extends Model
{

    use HasFactory;

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'user_id' => 'required|exists:users,id',
        'employee_id' => 'nullable|exists:users,id',
        'booking_status_id' => 'required|exists:booking_statuses,id',
        'payment_id' => 'nullable|exists:payments,id'
    ];
    public $table = 'bookings';
    public $fillable = [
        'salon',
        'e_services',
        'options',
        'quantity',
        'user_id',
        'employee_id',
        'booking_status_id',
        'address',
        'payment_id',
        'coupon',
        'taxes',
        'purchase_taxes',
        'booking_at',
        'start_at',
        'ends_at',
        'hint',
        'cancel',
        'original_booking_id',
        'reported_from_id',
        'report_reason',
        'cancellation_reason',
        'cancelled_by'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'salon' => Salon::class,
        'e_services' => EServiceCollectionCast::class,
        'options' => OptionCollectionCast::class,
        'address' => Address::class,
        'coupon' => Coupon::class,
        'taxes' => TaxCollectionCast::class,
        'purchase_taxes' => TaxCollectionCast::class,
        'booking_status_id' => 'integer',
        'payment_id' => 'integer',
        'duration' => 'double',
        'quantity' => 'integer',
        'user_id' => 'integer',
        'employee_id' => 'integer',
        'original_booking_id' => 'integer',
        'reported_from_id' => 'integer',
        'booking_at' => 'datetime:Y-m-d\TH:i:s.uP',
        'start_at' => 'datetime:Y-m-d\TH:i:s.uP',
        'ends_at' => 'datetime:Y-m-d\TH:i:s.uP',
        'cancelled_at' => 'datetime:Y-m-d\TH:i:s.uP',
        'hint' => 'string',
        'report_reason' => 'string',
        'cancellation_reason' => 'string',
        'cancelled_by' => 'string',
        'cancel' => 'boolean'
    ];
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields',
        'duration',
        'at_salon',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'creating' => BookingCreatingEvent::class,
        'updating' => BookingCreatingEvent::class,
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
        if(!is_null($this) ){
            $array = parent::toArray();
            $array['total'] = $this->getTotal();
            $array['sub_total'] = $this->getSubtotal();
            $array['taxes_value'] = $this->getTaxesValue();
            return $array;
        }
        return [] ;
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
        foreach ($this->options as $option) {
            $total += $option->price * ($this->quantity >= 1 ? $this->quantity : 1);
        }
        return $total;
    }

    public function getTaxesValue(): float
    {
        $total = $this->getSubtotal();
        $taxValue = 0;
        if(!empty($this->taxes)){
            foreach ($this->taxes as $tax) {
                if ($tax->type == 'percent') {
                    $taxValue += ($total * $tax->value / 100);
                } else {
                    $taxValue += $tax->value;
                }
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

    public function getDurationAttribute(): float
    {
        return $this->getDurationInHours();
    }

    public function getDurationInHours(): float
    {
        if ($this->start_at) {
            if ($this->ends_at) {
                $endAt = new Carbon($this->ends_at);
            } else {
                $endAt = (new Carbon())->now();
            }
            $startAt = new Carbon($this->start_at);
            $hours = $endAt->diffInSeconds($startAt) / 60 / 60;
            $hours = round($hours, 2);
        } else {
            $hours = 0;
        }
        return $hours;
    }

    public function getAtSalonAttribute(): bool
    {
        if($this->address && $this->salon){
            return $this->address->id == ( is_null($this->salon->address)? $this->salon->address_id : $this->salon->address->id ) ;
        } else return false ;  
    }

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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }

    /**
     * @return BelongsTo
     **/
    public function bookingStatus(): BelongsTo
    {
        return $this->belongsTo(BookingStatus::class, 'booking_status_id', 'id');
    }

    /**
     * @return BelongsTo
     **/
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'id');
    }

    /**
     * 
     *  REPORT SECTION STARTS HERE
     * 
     */

     public function originalBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'original_booking_id', 'id');
    }

    public function reportedFrom(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'reported_from_id', 'id');
    }

    public function reportedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'reported_from_id', 'id');
    }

    public function isReported(): bool
    {
        return !is_null($this->reported_from_id);
    }

    public function hasBeenReported(): bool
    {
        return $this->reportedBookings()->exists();
    }

    public function getCurrentBooking(): ?Booking
    {
        // Si ce RDV n'est pas reporté, c'est le current
        if (!$this->hasBeenReported()) {
            return $this;
        }

        // Sinon, chercher le dernier de la chaîne
        $originalId = $this->original_booking_id ?: $this->id;
        
        return self::where('original_booking_id', $originalId)
            ->whereNotIn('booking_status_id', [8]) // Pas Reported
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function canBeReported(): bool
    {
        return !$this->cancel && 
               !in_array($this->booking_status_id, [6, 7, 9]) && // Done, Failed, Reported
               $this->booking_at > now();
    }

    public function canBeCancelled(): bool
    {
        return !$this->cancel && 
               !in_array($this->booking_status_id, [6, 7, 9]); // Done, Failed, Reported
    }

    /**
     * 
     *  REPORT SECTION ENDS HERE
     * 
     */

}
