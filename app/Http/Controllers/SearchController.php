<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product; // مدل محصولت

class SearchController extends Controller
{
    public function search(Request $request) // Changed method name to 'search'
    {
        $query = $request->get('q');

        // Changed 'name' to 'title' as per database schema
        $results = Product::where('title', 'like', "%$query%")
            ->orWhere('description', 'like', "%$query%")
            ->limit(10)
            ->get();

        return response()->json($results);
    }
}
