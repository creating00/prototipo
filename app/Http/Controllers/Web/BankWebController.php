<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\BankWebRequest;
use App\Models\Bank;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BankWebController extends Controller
{
    use AuthorizesRequests;
    
    public function index()
    {
        $this->authorize('viewAny', Bank::class);

        $banks = $this->getAllBanks();
        $rowData = $this->getAllBanksForDataTable();

        $headers = ['#', 'Nombre', 'Creado en'];
        $hiddenFields = ['id'];

        return view('admin.bank.index', compact(
            'banks',
            'rowData',
            'headers',
            'hiddenFields'
        ));
    }

    private function getAllBanks()
    {
        return Bank::orderBy('name')->get();
    }

    private function getAllBanksForDataTable()
    {
        $banks = $this->getAllBanks();

        return $banks->map(function ($bank, $index) {
            return [
                'id' => $bank->id,
                'number' => $index + 1,
                'name' => $bank->name,
                'created_at' => $bank->created_at?->format('Y-m-d'),
            ];
        })->toArray();
    }

    public function create()
    {
        $this->authorize('create', Bank::class);

        return view('admin.bank.create');
    }

    public function store(BankWebRequest $request)
    {
        $this->authorize('create', Bank::class);

        Bank::create($request->validated());

        return redirect()
            ->route('web.banks.index')
            ->with('success', 'Banco creado correctamente');
    }

    public function edit($id)
    {
        $bank = Bank::findOrFail($id);
        $this->authorize('update', $bank);

        return view('admin.bank.edit', compact('bank'));
    }

    public function update(BankWebRequest $request, $id)
    {
        $bank = Bank::findOrFail($id);
        $this->authorize('update', $bank);

        $bank->update($request->validated());

        return redirect()
            ->route('web.banks.index')
            ->with('success', 'Banco actualizado correctamente');
    }

    public function destroy($id)
    {
        $bank = Bank::findOrFail($id);
        $this->authorize('delete', $bank);

        $bank->delete();

        return redirect()
            ->route('web.banks.index')
            ->with('success', 'Banco eliminado correctamente');
    }
}
