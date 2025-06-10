<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SearchController extends Controller
{
    //
}
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product; // مدل محصولت

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q');

        $results = Product::where('name', 'like', "%$query%")
            ->orWhere('description', 'like', "%$query%")
            ->limit(10)
            ->get();

        return response()->json($results);
    }
}