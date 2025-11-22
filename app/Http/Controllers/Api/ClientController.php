<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        return Client::all();
    }

    public function search(Request $request)
    {
        $query = Client::query();

        if ($request->filled('search')) {
            $searchTerm = $request->search;

            $query->where(function ($q) use ($searchTerm) {
                $q->where('full_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('document', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('phone', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('address', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Límite por defecto 50, configurable via ?limit=20
        $limit = $request->get('limit', 10);

        return $query->limit($limit)->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document' => 'required|unique:clients',
            'full_name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        return Client::create($validated);
    }

    public function show(Client $client)
    {
        return $client;
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'document' => 'required|unique:clients,document,' . $client->id,
            'full_name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $client->update($validated);

        return $client;
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return response()->json(['message' => 'Client deleted']);
    }
}
