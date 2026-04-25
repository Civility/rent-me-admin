<?php namespace RentMe\Rent\Models;

use Carbon\Carbon;
use Model;

class Car extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];

    protected const SERVICE_BUFFER_MINUTES = 30;

    public $belongsTo = [
        'category' => ['RentMe\Rent\Models\Categories'],
        'type' => ['RentMe\Rent\Models\Type'],
    ];

    public $hasMany = [
        'orders' => ['RentMe\Rent\Models\Orders', 'key' => 'car_id'],
    ];

    // public $attachMany = [
    //     'images' => ['System\Models\File', 'order' => 'sort_order']
    // ];

    // public $attachOne = [
    //     'img' => ['System\Models\File'],
    // ];

    public $table = 'rentme_rent_cars';

    public $rules = [
        'transmission' => 'in:A,M,CVT,Semi-Automatic',
        'fuel' => 'in:Petrol,Diesel,Electric,Hybrid,LPG',
        'body' => 'in:Sedan,SUV,Hatchback,Coupe,Convertible,Wagon,Van,Truck',
        'drive_type' => 'in:FWD,RWD,AWD,4WD',
    ];

    public $jsonable = ['images'];

  public function scopeAvailableForOrder($query, $order)
    {
        if (empty($order->date_from) || empty($order->date_to)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $from = Carbon::parse($order->date_from);
        $to = Carbon::parse($order->date_to);

        if (!empty($order->location_id)) {
            $query->where('location_id', $order->location_id);
        }

        $query->whereDoesntHave('orders', function ($orderQuery) use ($from, $to, $order) {
            $orderQuery
                ->where('id', '!=', $order->id)
                ->where('date_from', '<', $to)
                ->whereRaw('DATE_ADD(date_to, INTERVAL ? MINUTE) > ?', [
                    static::SERVICE_BUFFER_MINUTES,
                    $from->format('Y-m-d H:i:s')
                ]);
        });
    }

    public function hasReservationBetween($from, $to): bool
    {
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);

        return $this->orders()
            ->where('date_from', '<', $to)
            ->whereRaw('DATE_ADD(date_to, INTERVAL ? MINUTE) > ?', [
                static::SERVICE_BUFFER_MINUTES,
                $from->format('Y-m-d H:i:s')
            ])
            ->exists();
    }

    public function scopeAvailableBetween($query, $from, $to)
    {
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);

        return $query->whereDoesntHave('orders', function ($orderQuery) use ($from, $to) {
            $orderQuery
                ->where('date_from', '<', $to)
                ->whereRaw('DATE_ADD(date_to, INTERVAL ? MINUTE) > ?', [
                    static::SERVICE_BUFFER_MINUTES,
                    $from->format('Y-m-d H:i:s')
                ]);
        });
    }

    public function getCurrentOrderAttribute()
    {
        $now = Carbon::now();

        return $this->orders()
            ->where('date_from', '<=', $now)
            ->where('date_to', '>=', $now)
            ->orderBy('date_from')
            ->first();
    }

    public function getServiceOrderAttribute()
    {
        $now = Carbon::now();

        return $this->orders()
            ->where('date_to', '<', $now)
            ->whereRaw('DATE_ADD(date_to, INTERVAL ? MINUTE) >= ?', [
                static::SERVICE_BUFFER_MINUTES,
                $now->format('Y-m-d H:i:s')
            ])
            ->orderByDesc('date_to')
            ->first();
    }

    public function getNextReservationAttribute()
    {
        $now = Carbon::now();

        return $this->orders()
            ->where('date_from', '>', $now)
            ->orderBy('date_from')
            ->first();
    }

    public function getRelevantOrderAttribute()
    {
        if ($this->current_order) {
            return $this->current_order;
        }

        if ($this->service_order) {
            return $this->service_order;
        }

        if ($this->next_reservation) {
            return $this->next_reservation;
        }

        return $this->orders()
            ->orderByDesc('date_from')
            ->first();
    }

    public function getBookingStatusAttribute(): string
    {
        if ($this->current_order) {
            return 'rented';
        }

        if ($this->service_order) {
            return 'service';
        }

        if ($this->next_reservation) {
            return 'reserved';
        }

        if ($this->relevant_order) {
            return 'has_order';
        }

        return 'available';
    }
}
