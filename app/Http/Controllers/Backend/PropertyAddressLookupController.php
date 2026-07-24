<?php

namespace App\Http\Controllers\Backend;

use App\Services\AddressLookup\AddressLookupException;
use App\Services\AddressLookup\AddressLookupManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PropertyAddressLookupController
{
    public function search(Request $request, AddressLookupManager $lookup): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:150'],
        ]);

        try {
            return response()->json($lookup->search($validated['q']));
        } catch (AddressLookupException $exception) {
            Log::warning('Property address lookup failed.', [
                'provider' => config('address_lookup.default'),
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['message' => $exception->getMessage()], 503);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Address search is temporarily unavailable. You can still enter the address manually.',
            ], 503);
        }
    }

    public function resolve(Request $request, AddressLookupManager $lookup): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:500'],
        ]);

        try {
            $address = $lookup->resolve($validated['id']);

            if (! $address) {
                return response()->json(['message' => 'The selected address could not be found.'], 404);
            }

            return response()->json(['address' => $address]);
        } catch (AddressLookupException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The selected address could not be loaded. You can still enter it manually.',
            ], 503);
        }
    }
}
