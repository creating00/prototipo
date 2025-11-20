<?php

namespace App\Http\Controllers\Web;

use App\Models\Client;
use App\Http\Controllers\Controller;

class ClientWebController extends Controller
{
    public function index()
    {
        return view('admin.client.index');
    }

    public function create()
    {
        return view('admin.client.create');
    }

    public function edit($id)
    {
        $client = Client::findOrFail($id);
        return view('admin.client.edit', ['client' => $client]);
    }
}
