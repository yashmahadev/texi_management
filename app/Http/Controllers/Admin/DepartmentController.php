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
            'name' => 'required|string|max:255|unique:departments,name',
            'group' => 'required|in:Government,Corporate',
        ], [
            'name.required' => 'Please provide a department name.',
            'name.unique' => 'The department ":input" is already registered. Please use a unique name.',
            'group.required' => 'Please select a group (Government or Corporate).',
            'group.in' => 'The selected group is invalid. Please choose Government or Corporate.',
        ]);

        $department = Department::create([
            'name' => $request->name,
            'group' => $request->group,
        ]);

        return response()->json($department);
    }
}
