<?php namespace RentMe\Rent\Models;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Model;

/**
 * Model
 */
class Orders extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    use \Winter\Storm\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];
    protected $casts = [
        'date_from' => 'datetime',
        'date_to' => 'datetime',
    ];
    public $belongsTo = [
        'car' => ['RentMe\Rent\Models\Car', 'key' => 'car_id'],
        'location' => ['RentMe\Rent\Models\Locations', 'key' => 'location_id'],
        'return_location' => ['RentMe\Rent\Models\Locations', 'key' => 'return_location_id'],  // Для return_location_id
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'rentme_rent_order';

    /**
     * @var array Validation rules
     */
      public $rules = [
        'car_id' => 'required|exists:rentme_rent_cars,id',
        'location_id' => 'required|exists:rentme_rent_locations,id',
        'return_location_id' => 'required|exists:rentme_rent_locations,id',
        'date_from' => 'required|date',
        'date_to' => 'required|date|after:date_from',
        'first_name' => 'required|string',
        'last_name' => 'required|string',
        'email' => 'required|email',
        'phone' => 'required|string',
        'dob' => 'required|date',
    ];

    /**
     * @var array Attribute names to encode and decode using JSON.
     */
    public $jsonable = [];

    public function beforeSave()
    {
        if (app()->runningInBackend()) {
            return;
        }

        if ($this->getOverlappingOrder()) {
            throw new \Exception('Car is unavailable for the selected period');
        }
    }

    public function getOverlappingOrder()
    {
        if (empty($this->car_id) || empty($this->date_from) || empty($this->date_to)) {
            return null;
        }

        $from = Carbon::parse($this->date_from);
        $to = Carbon::parse($this->date_to);

        return self::query()
            ->where('car_id', $this->car_id)
            ->where('id', '!=', $this->id)
            ->where('date_from', '<', $to)
            ->whereRaw('DATE_ADD(date_to, INTERVAL 30 MINUTE) > ?', [
                $from->format('Y-m-d H:i:s')
            ])
            ->orderBy('date_from')
            ->first();
    }

    public function getCarAvailabilityWarningDataAttribute(): array
    {
        $order = $this->getOverlappingOrder();

        if (!$order) {
            return [
                'hasConflict' => false,
            ];
        }

        return [
            'hasConflict' => true,
            'order' => $order,
            'blockedUntil' => Carbon::parse($order->date_to)->addMinutes(30),
        ];
    }

    public function filterFields($fields, $context = null)
    {
        $userFields = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'dob',
        ];

        $hasUserData =
            filled($this->first_name) ||
            filled($this->last_name) ||
            filled($this->email) ||
            filled($this->phone) ||
            filled($this->dob);

        foreach ($userFields as $fieldName) {
            if (isset($fields->{$fieldName})) {
                $fields->{$fieldName}->readOnly = $hasUserData;
            }
        }
    }
}
