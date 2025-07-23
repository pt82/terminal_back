<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function authData($user): array
    {
        // Разрешить множественные логины для следующих user_id
//        if (!in_array($user->id, [82, 10, 4, 11, 11742, 12653, 15022, 12654, 15189, 12085]))
//            $user->tokens()->delete();

        $token = $user->createToken('token');

        $roles = DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->select('roles.id', 'roles.name', 'roles.slug')
            ->where('role_user.user_id', '=', $user->id)
            ->get();
        $department = Department::where('department_id', $user->department_id)->first();
        $department_data = [
            'use_category' => (boolean)$department->use_category ?? NULL,
            'use_paper_receipt' => (boolean)$department->use_paper_receipt ?? NULL,
            'use_el_receipt' => $department->use_el_receipt ?? NULL,
            'send_receipt' => $department->send_receipt ?? NULL,
            'text_cash' => $department->text_cash ?? NULL,
          ];
        $userData = [
            'user' => $user,
            'level' => $user->level(),
            'permissions' => $user->rolePerms(),
//            'use_system'=>
            'roles' => $roles,
            'requisites' => $department->requisites() ?? [],
            'department_data' => $department_data
        ];

        return ['logged_in' => true, 'token' => $token->plainTextToken, 'user_data' => $userData];
    }
}
