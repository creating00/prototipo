<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseClientController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClientController extends BaseClientController
{
    public function index(Request $request): JsonResponse
    {
        // Validamos que el branch_id sea enviado y exista
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id'
        ]);

        $branchId = (int) $request->input('branch_id');

        // El service ya usa el scope forBranch internamente
        $clients = $this->clientService->getAllClients($branchId);

        return response()->json($clients);
    }

    public function store(Request $request)
    {
        try {
            $client = $this->clientService->createClient($request->all());
            return response()->json($client, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function show($id)
    {
        $client = $this->clientService->getClientById($id);
        return response()->json($client);
    }

    public function update(Request $request, $id)
    {
        try {
            $client = $this->clientService->updateClient($id, $request->all());
            return response()->json($client);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function destroy($id)
    {
        try {
            $result = $this->clientService->deleteClient($id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }
}
