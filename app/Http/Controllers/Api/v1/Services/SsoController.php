<?php

namespace App\Http\Controllers\Api\v1\Services;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Log\LogAccess;
use App\Helpers\JwtAuthentication;
use App\Http\Controllers\Controller;
use App\Models\Sakip\Services\UserSimpeg;
use App\Models\Data\UserSakip;
use App\Models\User;
use App\Models\Sakip\MASTER\MasterOpd;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;

use Illuminate\Support\Facades\DB;

class SsoController extends Controller
{
    
    public function login(Request $request)
    {     
        try{

            $token = $request->bearerToken();

            if(!$token){
                return response()->json([
                    "success" => false,
                    "message" => "Token is Required",
                ]);
            }

            /*$credentials = $request->validate([
                'token' => ['required'],
            ]);*/

            $url = env('SSO_URL', 'http://localhost:8000') . "/api/users-services/v1/me";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get($url);

            if(!$response){
                return response()->json([
                    "success" => false,
                    "message" => "Unable to Connect ",
                    "data" => ""
                ]);
            }
            else
            {  
                
                $data_sso = $response->json();                

                if($response->status() !='200'){
                    return response()->json([
                        "success" => false,
                        "message" => $data_sso['error'],
                        "data" => ""
                    ]);

                }
                else
                    {
                       
                       $error_code = !empty($data_sso['code']) ? $data_sso['code'] : '';   
                       
                       if($error_code !='002'){
                            return response()->json([
                                "success" => false,
                                "message" => $data_sso['message'],
                                "data" => ""
                            ]);
                       }
                                $data_simpeg = $data_sso['data']['dataSimpeg'];
                                $nip         = !empty($data_simpeg['nip']) ? $data_simpeg['nip'] : '';
                                $opd         = !empty($data_simpeg['opd_id']) ? $data_simpeg['opd_id'] : '';
                                $nm_opd      = !empty($data_simpeg['opd_nm']) ? $data_simpeg['opd_nm'] : '';
                                $sub_opd     = !empty($data_simpeg['sub_opd_id']) ? $data_simpeg['sub_opd_id'] : '';
                                $nm_sub_opd  = !empty($data_simpeg['sub_opd_nm']) ? $data_simpeg['sub_opd_nm'] : '';
                                $opd_simpeg  = !empty($data_simpeg['opd_id']) ? $data_simpeg['opd_id'] : '';
                                $nama_asn    = !empty($data_simpeg['nama_pns']) ? $data_simpeg['nama_pns'] : '';
                                $eselon_id   = !empty($data_simpeg['eselon_id']) ? $data_simpeg['eselon_id'] : '';
                                $eselon_nm   = !empty($data_simpeg['eselon_nm']) ? $data_simpeg['eselon_nm'] : '';
                                $id_gol      = !empty($data_simpeg['golru_id']) ? $data_simpeg['golru_id'] : '';
                                $golongan    = !empty($data_simpeg['golru_nm']) ? $data_simpeg['golru_nm'] : '';
                                $pangkat     = !empty($data_simpeg['pangkat']) ? $data_simpeg['pangkat'] : '';
                                $jenjang     = !empty($data_simpeg['jenjang_id']) ? $data_simpeg['jenjang_id'] : '';
                                $jabatan_id  = !empty($data_simpeg['jabatan_id']) ? $data_simpeg['jabatan_id'] : '';
                                $jabatan_nm  = !empty($data_simpeg['jabatan_nm']) ? $data_simpeg['jabatan_nm'] : '';
                                $jns_jbtn_id = !empty($data_simpeg['jns_jbtn_id']) ? $data_simpeg['jns_jbtn_id'] : '';
                                $jns_jbtn_nm = !empty($data_simpeg['jns_jbtn_nm']) ? $data_simpeg['jns_jbtn_nm'] : '';


                                    //get master_opd_id
                                    $master_opd = DB::table('master_opd')
                                    ->where('simpeg_opd_id', '=', $opd)
                                    ->first();
                                    
                                    $master_opd_id = $master_opd->id;


                                    $insert_simpeg['master_opd_id'] = $master_opd_id;
                                    $insert_simpeg['opd_id'] = $opd;
                                    $insert_simpeg['opd_nm'] = $nm_opd;
                                    $insert_simpeg['sub_opd_id'] = $sub_opd;
                                    $insert_simpeg['sub_opd_nm'] = $nm_sub_opd;
                                    $insert_simpeg['jns_jbtn_id'] = $jns_jbtn_id;
                                    $insert_simpeg['jns_jbtn_nm'] = $jns_jbtn_id;
                                    $insert_simpeg['jabatan_id'] = $jabatan_id;
                                    $insert_simpeg['jabatan_nm'] = $jabatan_nm;
                                    $insert_simpeg['eselon_id'] = $eselon_id;
                                    $insert_simpeg['eselon_nm'] = $eselon_nm;
                                    $insert_simpeg['json_pegawai'] = "";
                                    $insert_simpeg['is_active'] = true;
                                    
                                    $cek_user = User::where('username', '=', $nip)->count();

                                    if($cek_user <= 0 )
                                    {
                                        //insert user
                                        $roles = ['39d57ab8-c480-4c61-a5d8-a662c5b66e27'];
                                        $insert['id'] = Str::uuid();
                                        $insert['name'] = $nama_asn;
                                        $insert['username'] = $nip;
                                        $insert['password'] = "ElPassword";
                                        $insert['current_role'] = "39d57ab8-c480-4c61-a5d8-a662c5b66e27";
                                        $insert['is_active'] = true;

                                        $user = User::create($insert);

                                        $roles = collect($roles)->map(function($item) use($nip){
                                            return [
                                                "id" => Str::uuid(),
                                                "role_id" => $item,
                                                "type" => "common",
                                                "created_by" => $nip,
                                                "updated_by" => $nip,
                                            ];
                                        });
                                        $user->role()->attach($roles);


                                        //INsert OPD User
                                        $userSakip = UserSakip::create([
                                            'id' => Str::uuid(),
                                            'user_id' => $user->id,
                                            'master_opd_id' => $master_opd_id,
                                            "created_by" => $nip,
                                            "updated_by" => $nip,
                                        ]);

                                        /*-----------------Cek Table User Simpeg------------------*/
                                        $id= Str::uuid();
                                        $user_id= $user->id;                                           
                                        
                                        $cek_user_simpeg = UserSimpeg::where('nip', '=', $nip)->count();

                                        if($cek_user_simpeg > 0)
                                            $simpeg_user_update = UserSimpeg::where('nip', '=', $nip)->update($insert_simpeg); 
                                        else{
                                            $insert_simpeg['id'] = Str::uuid();
                                            $insert_simpeg['user_id'] = $user_id;
                                            $insert_simpeg['nip'] = $nip;
                                            $simpeg_user = UserSimpeg::create($data_user_simpeg);    
                                        }    
                                        /*-----------------Cek Table User Simpeg------------------*/                                    
                                    }
                                    else
                                    {   
                                        /*-----------------Cek Table User ------------------*/
                                        $user = User::where('username', '=', $nip)->first();
                                        $edit_user['name'] = $nama_asn;
                                        $user_id           = $user->id;
                                        $username          = $user->username;
                                        $update_user       = User::where('username', $nip)->update($edit_user);
                                        /*-----------------Cek Table User ------------------*/

                                        /*-----------------Cek Table User Simpeg------------------*/
                                        $cek_user_simpeg = UserSimpeg::where('nip', '=', $nip)->count();

                                        if($cek_user_simpeg > 0)
                                             $simpeg_user_update = UserSimpeg::where('nip', '=', $nip)->update($insert_simpeg);                                            
                                        else{
                                            $insert_simpeg['id'] = Str::uuid();
                                            $insert_simpeg['user_id'] = $user_id;
                                            $insert_simpeg['nip'] = $username;
                                            $simpeg_user = UserSimpeg::create($insert_simpeg);    
                                        }
                                        /*-----------------Cek Table User Simpeg------------------*/                                        

                                    }


                                    //set biro jadi sekda
                                    //$biro = array('2876', '2863', '2809', '2596', '2796', '2836', '2889', '2823', '2849');
                                    //$new_opd = (in_array($opd_simpeg, $biro)) ? '36d2dee6-17b4-4ce8-87e0-01e097c36d6d' : $master_opd_id;
                                    
                                    //get OPD
                                    $opd = null;
                                    if(!empty($master_opd_id)){
                                        $dataOpd = MasterOpd::find($master_opd_id);
                                        
                                        $opd = [
                                            "id" => $dataOpd->id,
                                            "nama_opd" => $dataOpd->nama_opd,
                                            "kode_opd" => $dataOpd->kode_opd,
                                            "alias_opd" => $dataOpd->alias_opd,
                                            "ikd_opd_id" => $dataOpd->ikd_opd_id,
                                            "simpeg_opd_id" => $dataOpd->simpeg_opd_id
                                        ];
                                    }

                                    $level = 'Pegawai';
                                    $role='39d57ab8-c480-4c61-a5d8-a662c5b66e27';
                                    //$getTokenIkd = $this->getIkdTokenAccess();
                                    
                                    $payload = [
                                        "username" => $nip,
                                        "name" => $nama_asn,
                                        "nip" => $nip,
                                        "level" => $level,
                                        "role" => $role,
                                        "opd" => $opd,
                                        "opd_id" => $dataOpd->id,
                                        "jns_jbtn_id" => $jns_jbtn_id,
                                        "jns_jbtn_nm" => $jns_jbtn_nm,
                                        "jabatan_id" => $jabatan_id,
                                        "jabatan_nm" => $jabatan_nm,
                                        "eselon_id" => $eselon_id,
                                        "eselon_nm" => $eselon_nm,
                                        //"ikdtoken" => $getTokenIkd['response']['access_token'] ?? "",
                                    ];

                                    $token_access = JwtAuthentication::create($payload);

                                    LogAccess::create([
                                        "id"            => Str::uuid(),
                                        "user"          => $nip,
                                        "ip_address"    => $request->ip(),
                                        "user_agent"    => $request->header('User-Agent'),
                                        "unix_time"     => time()
                                    ]);
                        
                                    return response()->json([
                                        'success' => true,
                                        'token_access' => $token_access,
                                        'message' => 'Login Success',
                                    ]);
                    
                }
            }
                
            return response()->json([
                'success' => false,
                'message' => 'Username or password is incorrect.',
            ], 401); 
        }
        catch(\Exception $e)
        {
            return response()->json([
                'status' => 422,
                'message' => $e->getMessage()
            ],422);
        } 
    }
    
    private function getIkdTokenAccess()
    {
        $params = [
            'grant_type' =>  "client_credentials",
            'client_id'     =>  env("IKD_CLIENT_ID", ""),
            'client_secret' => env('IKD_CLIENT_SECRET', ""),
            'scope'         => "*" 
        ];
        try {
            $response = Http::withoutVerifying()->asForm()->post(env('IKD_URL','') . '/oauth/token', $params);
            if($response->failed()){
                return array(
                    "error" => true,
                    "message" => "failed response from api"
                );
            }
            return array(
                "error" => false,
                "message" => "success",
                "response" => $response->json()
            );
        } catch (\Exception $e) {
            return array(
                "error" => true,
                "message" => $e->getMessage()
            );
        }
    }
}
