<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\BaseClientController;
use Illuminate\Http\Request;

class ClientWebController extends BaseClientController
{
    public function index()
    {
        $rowData = $this->clientService->getAllClientsForDataTable();
        $clients = $this->clientService->getAllClients();

        $headers = ['#', 'Documento', 'Nombre Completo', 'Teléfono', 'Email', 'Creado en:'];
        $hiddenFields = ['id', 'is_system'];

        return view('admin.client.index', compact('clients', 'rowData', 'headers', 'hiddenFields'));
    }

    public function create()
    {
        return view('admin.client.create');
    }

    public function store(Request $request)
    {
        try {
            $client = $this->clientService->createClient($request->all());
            return redirect()->route('web.clients.index')
                ->with('success', 'Cliente creado exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function show($id)
    {
        $client = $this->clientService->getClientById($id);
        return view('admin.client.show', compact('client'));
    }

    public function edit($id)
    {
        $client = $this->clientService->getClientById($id);
        return view('admin.client.edit', compact('client'));
    }

    public function update(Request $request, $id)
    {
        try {
            $client = $this->clientService->updateClient($id, $request->all());
            return redirect()->route('web.clients.index')
                ->with('success', 'Cliente actualizado exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $this->clientService->deleteClient($id);
            return redirect()->route('web.clients.index')
                ->with('success', 'Cliente eliminado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
