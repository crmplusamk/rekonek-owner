<?php

namespace Modules\Privilege\App\Repositories;

use Illuminate\Support\Str;
use Modules\Privilege\App\Models\Role;

class RoleRepository
{

    public function getList($request = null)
    {
        $roles = Role::when(isset($request['search']), function ($query) use ($request) {
            $query->where('alias', 'ilike', '%'.$request['search'].'%');
        });

        if (isset($request['perpage'])) return $roles->paginate($request['perpage']);
        return $roles->get();

    }

    public function create($request)
    {
        $role = Role::create([
            'name' => Str::slug($request['name']),
            'alias' => $request['name'],
            'is_active' => $request['is_active'] ? true : false
        ]);

        return $role;
    }

    public function getById($id)
    {
        $role = Role::findOrFail($id);
        return $role;
    }

    public function detail($id)
    {
        $role = Role::where('id', $id)->withCount('users', 'permissions')->first();
        return $role;
    }

    public function update($request, $id)
    {
        $role = $this->getById($id)->update([
            'name' => Str::slug($request['name']),
            'alias' => $request['name'],
            'is_active' => $request['is_active']
        ]);

        return $role;
    }

    public function delete($id)
    {
        $role = $this->getById($id);
        if (!$role->is_delete) return 403;

        if ($role->users()->exists())
        {
            $role->update([
                'is_active' => false
            ]);
            return 200;
        }

        $role->delete();
        return 204;
    }

    public function status($id)
    {
        $role = $this->getById($id);
        $role->update([
            'is_active' => !$role->is_active
        ]);

        return $role;
    }

    public function datatable()
    {

        $roles = Role::withCount('permissions')
            ->when(request()->search, function ($query) {
                $query->where('alias', 'ilike', '%'.request()->search.'%');
            })
            ->when(request()->order[0], function ($query) {
                $orderMappings = [
                    "1" => 'alias',
                ];

                $column = request()->order[0]['column'];
                $dir    = request()->order[0]['dir'];

                if (isset($orderMappings[$column])) {
                    $query->orderBy($orderMappings[$column], $dir)
                        ->orderBy('id', 'desc');
                }
            });

        return datatables()->of($roles)

            ->addColumn('checkbox', function ($role) {
                return view('privilege::role.table_partials._checkbox', [
                    'role' => $role
                ]);
            })
            ->addColumn('name', function ($role) {
                return view('privilege::role.table_partials._name', [
                    'role' => $role
                ]);
            })
            ->addColumn('permission', function ($role) {
                return view('privilege::role.table_partials._permission', [
                    'role' => $role
                ]);
            })
            ->addColumn('status', function ($role) {
                return view('privilege::role.table_partials._status', [
                    'role' => $role
                ]);
            })
            ->addColumn('action', function ($role) {
                return view('privilege::role.table_partials._action', [
                    'role' => $role,
                ]);
            })
            ->make();
    }
}
