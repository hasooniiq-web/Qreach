<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\View\View;

/** Read-only catalogue of every permission key and which roles hold it. */
class PermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::with('roles')->orderBy('group')->orderBy('key')->get()->groupBy('group');

        return view('admin.permissions.index', compact('permissions'));
    }
}
