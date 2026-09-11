<?php

namespace App\Http\Controllers\Api;

class UserController
{
    public function index()
    {
        return ['users' => []];
    }


    public function all()
    {
        return ['users' => []];
    }

    public function show(int $id)
    {
        return ['id' => $id];
    }

    public function store()
    {
        return ['created' => true];
    }

    public function update(int $id)
    {
        return ['updated' => $id];
    }

    public function destroy(int $id)
    {
        return ['deleted' => $id];
    }
}
