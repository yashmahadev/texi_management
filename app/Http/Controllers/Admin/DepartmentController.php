<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $group = $request->query('group');
        $departments = Department::when($group, function($query) use ($group) {
            return $query->where('group', $group);
        })->get();

        return response()->json($departments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'group' => 'required|in:Government,Corporate',
        ]);

        $department = Department::create([
            'name' => $request->name,
            'group' => $request->group,
        ]);

        return response()->json($department);
    }
}
