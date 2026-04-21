<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RentMe\Rent\Models\Car;
use RentMe\Rent\Models\Locations;
use RentMe\Rent\Models\Orders;
use RentMe\Rent\Models\CallBack;
Route::prefix('api')->group(function () {

    Route::get('locations', function () {
        $locations = Locations::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(function ($location) {
                return [
                    'id' => $location->id,
                    'code' => $location->code,
                    'name' => $location->name,
                ];
            })
            ->values();

        return response()->json($locations, 200);
    });


    Route::get('cars', function (Request $request) {
        $validated = validator($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after:date_from',
            'location_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'type_id' => 'nullable|integer',
        ])->validate();

        $query = Car::query()
            ->with(['category', 'type'])
            ->availableBetween($validated['date_from'], $validated['date_to']);

        if (!empty($validated['location_id'])) {
            $query->where('location_id', (int) $validated['location_id']);
        }

        if (!empty($validated['category_id'])) {
            $query->where('category_id', (int) $validated['category_id']);
        }

        if (!empty($validated['type_id'])) {
            $query->where('type_id', (int) $validated['type_id']);
        }

        $shortMediaPath = function ($path) {
            if (!$path || !is_string($path)) {
                return null;
            }

            $path = str_replace('\\', '/', $path);
            $path = preg_replace('#/+#', '/', $path);
            $path = ltrim($path, '/');

            if ($path === '' || str_contains($path, '../') || str_starts_with($path, '..')) {
                return null;
            }

            return $path;
        };

        $cars = $query->get()->map(function (Car $car) use ($shortMediaPath) {
            $images = collect(is_array($car->images) ? $car->images : [])
                ->map(function ($item) use ($shortMediaPath) {
                    if (is_array($item) && array_key_exists('path', $item)) {
                        return $shortMediaPath($item['path']);
                    }

                    if (is_string($item)) {
                        return $shortMediaPath($item);
                    }

                    return null;
                })
                ->filter()
                ->values()
                ->all();

            return [
                'id' => $car->id,
                'name' => $car->name,
                // 'description' => $car->description,
                'category' => [
                    'id' => $car->category_id,
                    'name' => optional($car->category)->name,
                ],
                'type' => [
                    'id' => $car->type_id,
                    'name' => optional($car->type)->name,
                ],
                'img' => $shortMediaPath($car->img),
                'images' => $images,
                'price' => [
                    'priceDay' => (float) $car->price_day,
                    'excess' => (float) $car->excess,
                    'deposit' => (float) $car->deposit,
                    'discount' => (float) $car->discount,
                ],
                'features' => [
                    'year' => $car->year,
                    'seats' => $car->seats,
                    'doors' => $car->doors,
                    'bags' => $car->bags,
                    'ac' => (bool) $car->ac,
                    'age' => $car->min_age,
                    'transmission' => $car->transmission,
                    'fuel' => $car->fuel,
                    'horsepower' => $car->horsepower,
                    'body' => $car->body,
                    'driveType' => $car->drive_type,
                    'engineCapacity' => $car->engine_capacity,
                ],
            ];
        })->values();

        return response()->json($cars, 200);
    });

    Route::post('orders', function (Request $request) {
        $validated = validator($request->all(), [
            'car_id' => 'required|integer|exists:rentme_rent_cars,id',
            'location_id' => 'required|integer|exists:rentme_rent_locations,id',
            'return_location_id' => 'required|integer|exists:rentme_rent_locations,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after:date_from',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'dob' => 'required|date',
        ])->validate();

        $order = new Orders();
        $order->car_id = (int) $validated['car_id'];
        $order->location_id = (int) $validated['location_id'];
        $order->return_location_id = (int) $validated['return_location_id'];
        $order->date_from = $validated['date_from'];
        $order->date_to = $validated['date_to'];
        $order->first_name = $validated['first_name'];
        $order->last_name = $validated['last_name'];
        $order->email = $validated['email'];
        $order->phone = $validated['phone'];
        $order->dob = $validated['dob'];
        $order->is_active = true;

        try {
            $order->save();
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => [
                'id' => $order->id,
                'car_id' => $order->car_id,
                'location_id' => $order->location_id,
                'return_location_id' => $order->return_location_id,
                'date_from' => $order->date_from,
                'date_to' => $order->date_to,
                'first_name' => $order->first_name,
                'last_name' => $order->last_name,
                'email' => $order->email,
                'phone' => $order->phone,
                'dob' => $order->dob,
            ],
        ], 201);
    });

Route::post('callbacks', function (Request $request) {
    try {
        $validated = validator($request->all(), [
            'name'         => 'required|string|min:2|max:255|regex:/^[\pL\s\-\']+$/u',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'required|string|min:5|max:255',
            'message'      => 'required|string|min:6|max:5000',
            'locale'       => 'nullable|string|max:10',
            'website_url'  => 'nullable|max:0',
            'is_consent'   => 'nullable',
            'started_at'   => 'nullable|integer',
        ])->validate();

        if (!empty($request->input('website_url'))) {
            return response()->json(['success' => true], 201);
        }

        if ($request->boolean('is_consent') === true) {
            return response()->json(['success' => true], 201);
        }

        $startedAt = (int) $request->input('started_at');
        if ($startedAt > 0) {
            $elapsedMs = (int) round(microtime(true) * 1000) - $startedAt;
            if ($elapsedMs < 3000) {
                return response()->json(['success' => true], 201);
            }
        }

        $message = (string) $validated['message'];
        if (preg_match('/<[^>]+>|https?:\/\/|www\./i', $message)) {
            return response()->json([
                'success' => false,
                'message' => 'Links and HTML tags are not allowed.',
            ], 422);
        }

        $ip = $request->ip();
        $recentCount = CallBack::where('ip_address', $ip)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentCount >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        $callback = new CallBack();
        $callback->name       = trim($validated['name']);
        $callback->email      = $validated['email'] ?? null;
        $callback->phone      = trim($validated['phone']);
        $callback->message    = trim($validated['message']);
        $callback->locale     = $validated['locale'] ?? null;
        $callback->ip_address = $ip;
        $callback->user_agent = (string) $request->userAgent();
        $callback->status     = 'new';
        $callback->is_read    = false;
        $callback->save();

        return response()->json([
            'success' => true,
            'message' => 'Callback request saved successfully.',
            'data' => [
                'id'         => $callback->id,
                'name'       => $callback->name,
                'created_at' => $callback->created_at,
            ],
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $e->errors(),
        ], 422);
    } catch (\Throwable $e) {
        \Log::error('Callback API error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Server error. Please try again later.',
        ], 500);
    }
});
});
