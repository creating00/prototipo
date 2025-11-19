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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document' => 'required|unique:clients',
            'first_name' => 'required',
            'last_name' => 'required',
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
            'first_name' => 'required',
            'last_name' => 'required',
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
