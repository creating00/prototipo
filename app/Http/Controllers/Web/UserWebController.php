<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\BaseUserController;
use App\Http\Requests\User\UserWebRequest;
use App\Models\Branch;
use App\Models\Province;
use App\Services\User\UserDataTableService;
use App\ViewModels\UserFormData;
use App\Traits\AuthTrait;
use Spatie\Permission\Models\Role;

class UserWebController extends BaseUserController
{
    use AuthTrait;

    public function index(UserDataTableService $dataTableService)
    {
        $rowData = $dataTableService->getAllUsersForDataTable();

        $headers = [
            '#',
            'Nombre',
            'Correo Electrónico',
            'Sucursal',
            //'Estado',
            'Fecha de Alta'
        ];

        $hiddenFields = ['id'];

        return view('admin.user.index', compact('headers', 'rowData', 'hiddenFields'));
    }

    public function create()
    {
        $formData = new UserFormData(
            user: null,
            provinces: Province::orderBy('name')->get(),
            branches: Branch::orderBy('name')->get(),
            roles: Role::orderBy('name')->get(),
            statusOptions: ['active' => 'Activo', 'inactive' => 'Inactivo'],
            branchUserId: $this->currentBranchId()
        );

        return view('admin.user.create', compact('formData'));
    }

    public function store(UserWebRequest $request)
    {
        $this->userService->createUser($request->validated());

        return redirect()->route('web.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit($id)
    {
        $user = $this->userService->getUserById($id);

        $formData = new UserFormData(
            user: $user,
            provinces: Province::orderBy('name')->get(),
            branches: Branch::orderBy('name')->get(),
            roles: Role::orderBy('name')->get(),
            statusOptions: ['active' => 'Activo', 'inactive' => 'Inactivo'],
            branchUserId: $this->currentBranchId()
        );

        return view('admin.user.edit', compact('formData'));
    }

    public function update(UserWebRequest $request, $id)
    {
        $this->userService->updateUser($id, $request->validated());

        return redirect()->route('web.users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy($id)
    {
        $this->userService->deleteUser($id);

        return redirect()->route('web.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
