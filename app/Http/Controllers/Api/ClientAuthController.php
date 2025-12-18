<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Client;
use App\Models\ClientAccount;

class ClientAuthController extends Controller
{
    /**
     * Registro de cliente + cuenta
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'document' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar si ya existe una cuenta con este email
        if (ClientAccount::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'Ya existe una cuenta con este email'
            ], 422);
        }

        // Crear o actualizar cliente y la cuenta
        $client = $this->createOrUpdateClient($request);

        return response()->json([
            'message' => 'Cliente registrado correctamente',
            'client' => $client
        ], 201);
    }

    /**
     * Login de cliente
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $account = ClientAccount::with('client')->where('email', $credentials['email'])->first();

        if (!$account || !Hash::check($credentials['password'], $account->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        $token = $account->createToken('client-token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'account' => [
                'id' => $account->id,
                'email' => $account->email,
                'client' => [
                    'id' => $account->client->id,
                    'full_name' => $account->client->full_name,
                    'document' => $account->client->document,
                    'phone' => $account->client->phone
                ]
            ],
            'token' => $token
        ]);
    }

    /**
     * Logout de cliente
     */
    public function logout(Request $request)
    {
        // Usar el guard 'client' explícitamente
        $request->user('client')->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout exitoso']);
    }

    /**
     * Obtener información del cliente logueado
     */
    public function me(Request $request)
    {
        // Usar el guard 'client' explícitamente
        $account = $request->user('client');

        if (!$account) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        return response()->json([
            'message' => 'Operación exitosa',
            'account' => [
                'id' => $account->id,
                'email' => $account->email,
                'client' => [
                    'id' => $account->client->id,
                    'full_name' => $account->client->full_name,
                    'document' => $account->client->document,
                    'phone' => $account->client->phone
                ]
            ]
        ]);
    }

    /**
     * Método privado para crear o actualizar cliente y cuenta
     */
    private function createOrUpdateClient(Request $request)
    {
        // Buscar cliente por documento
        $client = Client::firstOrNew(['document' => $request->document]);

        // Actualizar o establecer campos
        $client->full_name = $request->full_name;
        $client->phone = $request->phone ?? $client->phone;
        $client->address = $request->address ?? $client->address;
        $client->save();

        // Crear cuenta de login asociada al cliente
        ClientAccount::create([
            'client_id' => $client->id,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return $client;
    }
}
