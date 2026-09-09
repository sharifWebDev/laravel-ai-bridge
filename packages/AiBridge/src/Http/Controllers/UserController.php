<?php

namespace App\Http\Controllers;

use App\Contracts\ToolProviderInterface;
use Illuminate\Http\Request;

class UserController extends Controller implements ToolProviderInterface
{
    public function getTools(): array
    {
        return [
            'exportUsers' => [
                "name" => "exportUsers",
                "description" => "Export user list in CSV or Excel format.",
                "permission" => "view-records",
                "parameters" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "format" => ["type" => "STRING", "description" => "csv or excel"]
                    ]
                ]
            ]
        ];
    }

    public function exportUsers(Request $request)
    {
        return response()->json(['message' => 'Exporting users...']);
    }
}
