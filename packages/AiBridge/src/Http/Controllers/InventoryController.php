<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['message' => 'Fetching inventory items...']);
    }
}
