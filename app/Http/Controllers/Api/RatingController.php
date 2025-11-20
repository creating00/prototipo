<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function index()
    {
        return Rating::with('product')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rate' => 'required|numeric|min:0|max:5',
            'count' => 'required|integer|min:0',
        ]);

        return Rating::create($validated)->load('product');
    }

    public function show(Rating $rating)
    {
        return $rating->load('product');
    }

    public function update(Request $request, Rating $rating)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rate' => 'required|numeric|min:0|max:5',
            'count' => 'required|integer|min:0',
        ]);

        $rating->update($validated);

        return $rating->load('product');
    }

    public function destroy(Rating $rating)
    {
        $rating->delete();

        return response()->json(['message' => 'Rating deleted']);
    }
}
