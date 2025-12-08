<?php

namespace Modules\User\App\Repositories;

use Illuminate\Support\Facades\Hash;
use Modules\Privilege\App\Models\Role;
use Modules\User\App\Models\User;

class UserRepository
{
    public function create($request)
    {
        $data = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
            'is_active' => $request['is_active']
        ]);

        $role = Role::find($request['role']);
        $data->assignRole($role);
        return $data;
    }

    public function getById($id)
    {
        $data = User::findOrFail($id);
        return $data;
    }

    public function detail($id)
    {
        $data = User::where('id', $id)->with('roles')->first();
        return $data;
    }

    public function update($request, $id)
    {
        $data = $this->getById($id);
        $data->update([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => $request['password'] ? Hash::make($request['password']) : $data->password,
            'is_active' => $request['is_active']
        ]);

        $role = Role::find($request['role']);
        $data->syncRoles($role);
        return $data;
    }

    public function delete($id)
    {
        $data = $this->getById($id);
        if (!$data->is_delete) return 403;

        $data->delete();
        return 204;
    }

    public function status($id)
    {
        $data = $this->getById($id);
        $data->update([
            'is_active' => !$data->is_active
        ]);

        return $data;
    }

    public function datatable()
    {
        $datatables = User::with('roles')
            ->when(request()->search, function ($query) {
                $query->where(function($query) {
                    $query->where('name', 'ilike', '%'.request()->search.'%')
                        ->orWhere('email', 'ilike', '%'.request()->search.'%');
                });
            })
            ->when(request()->order[0], function ($query) {
                $orderMappings = [
                    "1" => 'name',
                    "2" => 'email',
                ];

                $column = request()->order[0]['column'];
                $dir    = request()->order[0]['dir'];

                if (isset($orderMappings[$column])) {
                    $query->orderBy($orderMappings[$column], $dir)
                        ->orderBy('id', 'desc');
                }
            });

        return datatables()->of($datatables)

            ->addColumn('checkbox', function ($user) {
                return view('user::table_partials._checkbox', [
                    'user' => $user
                ]);
            })
            ->addColumn('name', function ($user) {
                return view('user::table_partials._name', [
                    'user' => $user
                ]);
            })
            ->addColumn('email', function ($user) {
                return view('user::table_partials._email', [
                    'user' => $user
                ]);
            })
            ->addColumn('role', function ($user) {
                return view('user::table_partials._role', [
                    'user' => $user
                ]);
            })
            ->addColumn('status', function ($user) {
                return view('user::table_partials._status', [
                    'user' => $user
                ]);
            })
            ->addColumn('action', function ($user) {
                return view('user::table_partials._action', [
                    'user' => $user,
                ]);
            })
            ->make();
    }
}
