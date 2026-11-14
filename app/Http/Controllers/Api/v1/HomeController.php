<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\JwtAuthentication;
use App\Http\Controllers\Controller;
use App\Models\Data\UserSimpeg;
use App\Models\Managements\Roleplay;
use App\Models\Managements\Roles;
use App\Models\Sakip\MASTER\MasterOpd;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    //
    public function me(Request $request)
    {
            return response()->json([
                "success" => true,
                "message" => "My Verified Profile",
                "data" => $request->get('payload')
            ],200);
       
    }

    public function getMyRoles(Request $request)
    {
        $payload = $request->get('payload');
        
        $user = User::where('username', $payload->username)->first();
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        $roles = Roleplay::select('roleplay.id', 'roleplay.role_id', 'roles.role_name')
        ->from('roleplay')
        ->join('roles', 'roles.id', '=', 'roleplay.role_id')
        ->where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    public function changeMyRole(Request $request)
    {
        $payload = $request->get('payload');
        $current_role = $payload->role;
        $new_role = $request->get('role_id');
        if($current_role == $new_role){
            return response()->json([
                'success' => false,
                'message' => 'Current role and new role are the same'
            ], 400);
        }
        $roleplay_id = $request->get('roleplay_id');
        $roleplay = Roleplay::find($roleplay_id);
        if(!$roleplay){
            return response()->json([
                'success' => false,
                'message' => 'Role tidak ditemukan'
            ], 404);
        }
        else{
            $user = User::find($roleplay->user_id);
            if(!$user){
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            else{
                $role = Roles::find($new_role);
                $user_simpeg = UserSimpeg::where('user_id', $user->id)->first();
                if(!$user_simpeg){
                    return response()->json([
                        'success' => false,
                        'message' => 'User simpeg not found'
                    ], 404);
                }
                else{
                    $opd = $user_simpeg->opd_nm;
                    $new_opd = $user_simpeg->opd_id;
                    $jns_jbtn_id = $user_simpeg->jns_jbtn_id;
                    $jns_jbtn_nm = $user_simpeg->jns_jbtn_nm;
                    $jabatan_id = $user_simpeg->jabatan_id;
                    $jabatan_nm = $user_simpeg->jabatan_nm;
                    $eselon_id = $user_simpeg->eselon_id;
                    $eselon_nm = $user_simpeg->eselon_nm;
                }
                $opd = null;
                if(!empty($user_simpeg->master_opd_id)){
                    $dataOpd = MasterOpd::find($user_simpeg->master_opd_id);
                    
                    $opd = [
                        "id" => $dataOpd->id,
                        "nama_opd" => $dataOpd->nama_opd,
                        "kode_opd" => $dataOpd->kode_opd,
                        "alias_opd" => $dataOpd->alias_opd,
                        "ikd_opd_id" => $dataOpd->ikd_opd_id,
                        "simpeg_opd_id" => $dataOpd->simpeg_opd_id
                    ];
                }
                $payload = [
                    "username" => $user->username,
                    "name" => $user->name,
                    "nip" => $user->username,
                    "level" => $role->role_name,
                    "role" => $role->id,
                    "opd" => $opd,
                    "opd_id" => $opd['id'],
                    "jns_jbtn_id" => $jns_jbtn_id,
                    "jns_jbtn_nm" => $jns_jbtn_nm,
                    "jabatan_id" => $jabatan_id,
                    "jabatan_nm" => $jabatan_nm,
                    "eselon_id" => $eselon_id,
                    "eselon_nm" => $eselon_nm,
                ];
                $token_access = JwtAuthentication::create($payload);
                return response()->json([
                    'success' => true,
                    'token_access' => $token_access,
                    'message' => 'Role changed successfully'
                ]);
            }
        }
    }
}
