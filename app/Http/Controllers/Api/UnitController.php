<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PresentsImages;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;

class UnitController extends Controller
{
    use PresentsImages;

    public function index(): JsonResponse
    {
        $units = Unit::query()
            ->orderBy('name')
            ->with('images')
            ->get(['id', 'name', 'type', 'price', 'capacity', 'stock', 'description']);

        return response()->json(['data' => $units->map(fn (Unit $unit): array => $this->withImages($unit))->values()]);
    }

    public function tickets(): JsonResponse
    {
        $tickets = Product::query()
            ->where('category', 'tiket_masuk')
            ->where('stock', '>', 0)
            ->orderBy('price')
            ->with('images')
            ->get(['id', 'name', 'category', 'price', 'type', 'stock']);

        return response()->json(['data' => $tickets->map(fn (Product $ticket): array => $this->withImages($ticket))->values()]);
    }
}
