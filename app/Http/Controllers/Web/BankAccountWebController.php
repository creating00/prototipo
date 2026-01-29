<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\BankAccount\BankAccountWebRequest;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\User;
use App\Traits\AuthTrait;

class BankAccountWebController extends Controller
{
    use AuthTrait;

    public function index()
    {
        $accounts = BankAccount::with(['bank', 'user'])->get();

        $headers = [
            '#',
            'Usuario',
            'Banco',
            'Alias',
            'Cuenta',
            'CBU',
            'Creado',
        ];

        $hiddenFields = ['id'];

        $rowData = $accounts->map(function ($account, $index) {
            return [
                'id' => $account->id,
                'number' => $index + 1,
                'user' => $account->user->name ?? '-',
                'bank' => $account->bank->name ?? '-',
                'alias' => $account->alias ?? '-',
                'account_number' => $account->account_number ?? '-',
                'cbu' => $account->cbu ?? '-',
                'created_at' => $account->created_at?->format('Y-m-d'),
            ];
        });

        return view('admin.bank_account.index', compact('accounts', 'rowData', 'headers', 'hiddenFields'));
    }

    public function create()
    {
        $formData = (object) [
            'bankAccount' => null,
            'banks' => Bank::orderBy('name')->pluck('name', 'id'),
            'users' => User::orderBy('name')->pluck('name', 'id'),
        ];

        return view('admin.bank_account.create', compact('formData'));
    }

    public function store(BankAccountWebRequest $request)
    {
        BankAccount::create($request->validated());

        return redirect()
            ->route('web.bank-accounts.index')
            ->with('success', 'Cuenta bancaria creada correctamente');
    }

    public function edit($id)
    {
        $formData = (object) [
            'bankAccount' => BankAccount::findOrFail($id),
            'banks' => Bank::orderBy('name')->pluck('name', 'id'),
            'users' => User::orderBy('name')->pluck('name', 'id'),
        ];

        return view('admin.bank_account.edit', compact('formData'));
    }

    public function update(BankAccountWebRequest $request, $id)
    {
        $account = BankAccount::findOrFail($id);
        $account->update($request->validated());

        return redirect()
            ->route('web.bank-accounts.index')
            ->with('success', 'Cuenta bancaria actualizada');
    }

    public function destroy($id)
    {
        BankAccount::findOrFail($id)->delete();

        return redirect()
            ->route('web.bank-accounts.index')
            ->with('success', 'Cuenta bancaria eliminada');
    }
}
