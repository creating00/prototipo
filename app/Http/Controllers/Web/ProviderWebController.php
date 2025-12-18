<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\BaseProviderController;
use Illuminate\Http\Request;

class ProviderWebController extends BaseProviderController
{
    public function index()
    {
        $rowData = $this->providerService->getAllProvidersForDataTable();
        $providers = $this->providerService->getAllProviders();

        $headers = [
            '#',
            'Razón Social',
            'CUIT',
            // 'Alias',
            'Contacto',
            // 'Email',
            'Teléfono',
            'Dirección',
            'Creado en'
        ];
        $hiddenFields = ['id'];

        return view('admin.provider.index', compact('providers', 'rowData', 'headers', 'hiddenFields'));
    }

    public function create()
    {
        return view('admin.provider.create');
    }

    public function store(Request $request)
    {
        try {
            $provider = $this->providerService->createProvider($request->all());
            return redirect()->route('web.providers.index')
                ->with('success', 'Provider created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function edit($id)
    {
        $provider = $this->providerService->getProviderById($id);
        return view('admin.provider.edit', compact('provider'));
    }

    public function update(Request $request, $id)
    {
        try {
            $provider = $this->providerService->updateProvider($id, $request->all());
            return redirect()->route('web.providers.index')
                ->with('success', 'Provider updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $this->providerService->deleteProvider($id);
            return redirect()->route('web.providers.index')
                ->with('success', 'Provider deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
