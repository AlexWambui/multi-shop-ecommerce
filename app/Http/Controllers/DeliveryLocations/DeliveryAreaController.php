<?php

namespace App\Http\Controllers\DeliveryLocations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Exception;
use App\Models\DeliveryArea;
use App\Models\DeliveryLocation;
use App\Http\Requests\DeliveryLocations\DeliveryAreaRequest;

class DeliveryAreaController extends Controller
{
    public function create(DeliveryLocation $delivery_location)
    {
        return inertia('app/delivery-locations/areas/Create', [
            'delivery_location' => $delivery_location
        ]);
    }

    public function store(DeliveryLocation $delivery_location, DeliveryAreaRequest $request)
    {
        try {
            DB::beginTransaction();

            $delivery_location->areas()->create($request->validated());

            DB::commit();

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Area created successfully"
            ]);

            return to_route('delivery-locations.show', $delivery_location);
        } catch (Exception $e) {
            DB::rollBack();

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Failed to create area: {$e->getMessage()}"
            ]);

            return back()->withInput();
        }
    }

    public function edit(string $delivery_location, string $delivery_area)
    {
        $delivery_location = DeliveryLocation::where('uuid', $delivery_location)->firstOrFail();
        $delivery_area = DeliveryArea::where('uuid', $delivery_area)->firstOrFail();

        return inertia('app/delivery-locations/areas/Edit', [
            'delivery_area' => $delivery_area,
            'delivery_location' => $delivery_location
        ]);
    }

    public function update(DeliveryLocation $delivery_location, DeliveryArea $delivery_area, DeliveryAreaRequest $request)
    {
        try {
            DB::beginTransaction();

            $delivery_area->update($request->validated());

            DB::commit();

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Area updated successfully"
            ]);

            return to_route('delivery-locations.show', $delivery_location->uuid);
        } catch (Exception $e) {
            DB::rollBack();

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Failed to create area: {$e->getMessage()}"
            ]);

            return back()->withInput();
        }
    }

    public function destroy(DeliveryArea $delivery_area)
    {
        try {
            $delivery_area->delete();

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Area deleted successfully"
            ]);

            return back();
        } catch (Exception $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Failed to delete area: {$e->getMessage()}"
            ]);

            return back()->withInput();
        }
    }
}
