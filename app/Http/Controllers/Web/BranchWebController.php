<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;

class BranchWebController extends Controller
{
    public function index()
    {
        return view('admin.branch.index');
    }

    public function create()
    {
        return view('admin.branch.create');
    }

    public function edit($id)
    {
        return view('admin.branch.edit', ['id' => $id]);
    }
}
