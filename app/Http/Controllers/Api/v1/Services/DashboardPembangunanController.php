<?php

namespace App\Http\Controllers\Api\v1\Services;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;

use App\Models\Sakip\MASTER\MasterSatuan;
use App\Models\Sakip\MASTER\MasterOpd;
use App\Http\Controllers\Api\v1\MASTER\BaseController;
use App\Models\Sakip\OPD\SasaranOpd;
use App\Models\Sakip\OPD\IndikatorOpd;
use App\Models\Sakip\OPD\Rencana;
use App\Models\Sakip\OPD\RencanaLangkah;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;

use App\Models\Sakip\KDH\PohonKinerjaSasaran;
use App\Models\Sakip\KDH\PohonKinerjaIndikator;
use App\Models\Sakip\KDH\Rkpd;
use App\Models\Sakip\KDH\PerjanjianKinerja;
use App\Models\Sakip\KDH\PerjanjianKinerjaProgram;
use App\Models\Sakip\KDH\RencanaAksi;
use App\Models\Sakip\KDH\RencanaAksiLangkah;

class DashboardPembangunanController extends Controller
{
    public function entrianopd(Request $request)
    {
        $tahun = $request->tahun;
        $master_opd_id = $request->master_opd_id;

        $opd = MasterOpd::select('id', 'kode_opd', 'nama_opd', 'simpeg_opd_id');
        if($master_opd_id != ''){           
            $opd->where('id', '=', $request->master_opd_id);
        }
        $finalResult = $opd->get();

        $opd = $finalResult->map(function($do) use($request, $tahun) 
        {
            $opd_id = $do->id;
            $tujuan = BaseController::getTujuanOPD($opd_id);
            $indikator_sasaran = IndikatorOpd::join('sasaran_opd', 'sasaran_opd.id', '=', 'indikator_opd.sasaran_opd_id')
                                 ->where('sasaran_opd.parent_id', '=', 0)
                                 ->where('indikator_opd.is_tujuan', false)
                                 ->where('indikator_opd.is_active', true)
                                 ->where('indikator_opd.master_opd_id', $opd_id)
                                 ->whereIn('sasaran_opd.tujuan_opd_id', $tujuan)
                                 ->whereNull('sasaran_opd.deleted_at')
                                 ->where('sasaran_opd.is_active', true)
                                 ->select('indikator_opd.id')
                                 ->pluck('indikator_opd.id')
                                 ->toArray();
          
            $rencana_aksi = DB::table('rencana_opd')
                    ->where('tahun', '=', $tahun)
                    ->where('master_opd_id', '=', $opd_id)
                    ->whereIn('indikator_opd_id', $indikator_sasaran)
                    ->where(function (Builder $query) {
                        $query->orWhereNotNull('target_tw1')
                              ->orWhereNotNull('target_tw2')
                              ->orWhereNotNull('target_tw3')
                              ->orWhereNotNull('target_tw4');
                        })
                    ->count();
            
            $realisasi = DB::table('rencana_opd')
                    ->where('tahun', '=', $tahun)
                    ->where('master_opd_id', '=', $opd_id)
                    ->whereIn('indikator_opd_id', $indikator_sasaran)
                    ->where(function (Builder $query) {
                        $query->orWhereNotNull('realisasi_tw1')
                              ->orWhereNotNull('realisasi_tw2')
                              ->orWhereNotNull('realisasi_tw3')
                              ->orWhereNotNull('realisasi_tw4');
                        })
                    ->count();
            
            
            $jml_indikator = count($indikator_sasaran);

            $jml_aksi      = ($jml_indikator!=0) ? $rencana_aksi/$jml_indikator * 100 : 0;
            $jml_realisasi = ($jml_indikator !=0) ? $realisasi/$jml_indikator * 100 : 0;
    
            return [
                "id"                => $do->id,
                "kode_opd"          => $do->kode_opd,
                "simpeg_opd_id"     => $do->simpeg_opd_id,
                "nama_opd"          => $do->nama_opd,
                "jumlah_indikator"  => $jml_indikator,
                "target_terisi"     => $rencana_aksi,
                "realisasi_terisi"  => $realisasi,
                "entrian_target"    => $jml_aksi,
                "entrian_realisasi" => $jml_realisasi
            ];
        });    

         return response()->json([
            'success' => true,
            'message' => 'Persentase Entrian Rencana Aksi OPD '.$tahun.' ',
            'data' => $opd
        ]); 
    }

    public function capaiankdh(Request $request)
    {
        //
        $searchColumn = collect(['sasaran']);
        $sasaran_id = $request->get('sasaran_id');
        $indikator_id = $request->get('indikator_id');
        

        $currentPage = $request->get('page', 1);
        $perPage = $request->get('per_page', 1000);
        $search = $request->get('search', '');

        $totalPage = 0;
        $totalRecord = 0;
        try {


            $tujuan = BaseController::getTujuanPemda();
            $query = PohonKinerjaSasaran::query();           
            if($search != ''){
                $searchColumn->map(function($item, $index) use($search, $query){
                    if($index == 0) $query->where($item, 'like', '%' . $search . '%');
                    else $query->orWhere($item, 'like', '%' . $search . '%');

                });
            }
            $query->whereIn('pohon_kinerja_tujuan_id', $tujuan);
            if(isset($sasaran_id))
                $query->where('id', $sasaran_id);
            $query->join('pohon_kinerja_tujuan', 'pohon_kinerja_tujuan.id', '=', 'pohon_kinerja_sasaran.pohon_kinerja_tujuan_id');
            $query->join('pohon_kinerja_misi', 'pohon_kinerja_misi.id', '=', 'pohon_kinerja_tujuan.pohon_kinerja_misi_id');
            $query->select('pohon_kinerja_sasaran.*');
            $query->orderBy('pohon_kinerja_misi.order', 'asc');
            $query->orderBy('pohon_kinerja_tujuan.order', 'asc');
            $query->orderBy('pohon_kinerja_sasaran.order', 'asc');
            $objData = $query->get();

            // remap
            $objData = $objData->map(function($item) use($request) {
                $jam = Carbon::parse($item->created_at)->diffInHours();
                if($jam > 24) {
                    $created_at = Carbon::parse($item->created_at)->format('d M Y H:i');
                }
                else
                {
                    $created_at = Carbon::createFromFormat('Y-m-d H:i:s', $item->created_at)->diffForHumans();
                }
                
                $indikator = PohonKinerjaIndikator::where('is_tujuan', '=', false)
                                          ->where('pohon_kinerja_sasaran_id','=', $item->id)
                                          ->get();
                
                $anggaran_murni = PerjanjianKinerjaProgram::where('pohon_kinerja_sasaran_id', '=', $item->id)
                ->where('tahun', '=',$request->tahun)
                ->where('murni', '=',true)
                ->sum('anggaran');

                $anggaran_perubahan = PerjanjianKinerjaProgram::where('pohon_kinerja_sasaran_id', '=', $item->id)
                ->where('tahun', '=',$request->tahun)
                ->where('murni', '=',false)
                ->sum('anggaran');

                $indikator = $indikator->map(function($ik) use($request) 
                {
                    $rencana_aksi = RencanaAksi::where('pohon_kinerja_indikator_id', '=',$ik->id)
                            ->where('tahun', '=', $request->tahun)
                            ->first(['id', 'target_tw1', 'target_tw2', 'target_tw3', 'target_tw4', 'tahun',
                                    'realisasi_tw1', 'realisasi_tw2', 'realisasi_tw3', 'realisasi_tw4',
                                'capaian_tw1', 'capaian_tw2', 'capaian_tw3', 'capaian_tw4', 'hambatan', 'tindak_lanjut'  ]);

                    $langkah = RencanaAksiLangkah::where('pohon_kinerja_indikator_id', '=', $ik->id)
                               ->where('tahun', '=',$request->tahun)
                               ->get();  
                    $langkah = $langkah->map(function($dl) use($request) 
                    {
                        return [
                            "id"               => $dl->id,
                            "langkah"          => $dl->langkah,
                            "target_tw1"       => $dl->target_tw1,
                            "target_tw2"       => $dl->target_tw2,
                            "target_tw3"       => $dl->target_tw3,
                            "target_tw4"       => $dl->target_tw4,
                            "realisasi_tw1"    => $dl->realisasi_tw1,
                            "realisasi_tw2"    => $dl->realisasi_tw2,
                            "realisasi_tw3"    => $dl->realisasi_tw3,
                            "realisasi_tw4"    => $dl->realisasi_tw4,
                            "capaian_tw1"      => $dl->capaian_tw1,
                            "capaian_tw2"      => $dl->capaian_tw2,
                            "capaian_tw3"      => $dl->capaian_tw3,
                            "capaian_tw4"      => $dl->capaian_tw4
                        ];
                    });                

                    $target_pk = PerjanjianKinerja::where('pohon_kinerja_indikator_id', '=', $ik->id)
                    ->where('tahun', '=',$request->tahun)
                    ->get(['target', 'tahun', 'murni'])->first(); 

                    return $listindikator = [
                        "id"              => $ik->id,
                        "indikator"       => $ik->indikator,
                        "satuan"          => BaseController::getSatuanByID($ik->satuan_id),
                        "rencana_aksi"    => $rencana_aksi,
                        "target_tw1"      => !empty($rencana_aksi) ? $rencana_aksi->target_tw1 : null,
                        "target_tw2"      => !empty($rencana_aksi) ? $rencana_aksi->target_tw2 : null,
                        "target_tw3"      => !empty($rencana_aksi) ? $rencana_aksi->target_tw3 : null,
                        "target_tw4"      => !empty($rencana_aksi) ? $rencana_aksi->target_tw4 : null,
                        "realisasi_tw1"   => !empty($rencana_aksi) ? $rencana_aksi->realisasi_tw1 : null,
                        "realisasi_tw2"   => !empty($rencana_aksi) ? $rencana_aksi->realisasi_tw2 : null,
                        "realisasi_tw3"   => !empty($rencana_aksi) ? $rencana_aksi->realisasi_tw3 : null,
                        "realisasi_tw4"   => !empty($rencana_aksi) ? $rencana_aksi->realisasi_tw4 : null,
                        "capaian_tw1"     => !empty($rencana_aksi) ? $rencana_aksi->capaian_tw1 : null,
                        "capaian_tw2"     => !empty($rencana_aksi) ? $rencana_aksi->capaian_tw2 : null,
                        "capaian_tw3"     => !empty($rencana_aksi) ? $rencana_aksi->capaian_tw3 : null,
                        "capaian_tw4"     => !empty($rencana_aksi) ? $rencana_aksi->capaian_tw4 : null,
                        "hambatan"        => !empty($rencana_aksi) ? $rencana_aksi->hambatan : null,
                        "tindak_lanjut"   => !empty($rencana_aksi) ? $rencana_aksi->tindak_lanjut : null,
                        "langkah"         => $langkah,
                        "target_perjanjian_kinerja" => $target_pk
                    ];
                });

                $list =  [
                    "id"                          => $item->id,
                    "pohon_kinerja_sasaran_id"    => $item->pohon_kinerja_sasaran_id,
                    "sasaran"                     => $item->sasaran,
                    "order"                       => $item->order,
                    "anggaran_perjanjian_kinerja" => ['murni'=> $anggaran_murni, 'perubahan'=>$anggaran_perubahan],
                    "indikator"                   => $indikator
                ];
                
                return $list;
            });

            return response()->json([
                'success' => true,
                'message' => 'List of Rencana Aksi Edit',
                'data' => $objData
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'List of Rencana Aksi Edit',
                'data' => [],
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function capaianopd(Request $request)
    {
        $master_opd_id = $request->master_opd_id;

         // cek existing opd
        $opd = MasterOpd::find($master_opd_id);
        if (!$opd) {
            // jika data tidak ditamukan di dalam database
            return response()->json([
                'success' => false,
                'message' => 'Perangkat Daerah not found.',
            ], 404);
        }

        $tujuan = BaseController::getTujuanOPD($master_opd_id);

       
        $sasaran = SasaranOpd::join('pk_opd', 'pk_opd.sasaran_opd_id', '=', 'sasaran_opd.id')
                        ->whereIn('sasaran_opd.tujuan_opd_id', $tujuan)
                        ->where('sasaran_opd.parent_id', 0)
                        ->where('pk_opd.tahun', $request->tahun)
                        ->where('sasaran_opd.is_active', true)
                        ->orderBy('sasaran_opd.order', 'asc')
                        ->distinct()
                        ->get(['sasaran_opd.id', 'sasaran_opd.sasaran', 'sasaran_opd.order', 'sasaran_opd.tujuan_opd_id']);

        $sasaran = $sasaran->map(function($item) use ($request)
        {    
            $master_opd_id = $request->master_opd_id;

            $indikator_sasaran = IndikatorOpd::join('pk_opd', 'pk_opd.indikator_opd_id', '=', 'indikator_opd.id') 
                                ->where('indikator_opd.sasaran_opd_id', $item->id) 
                                ->where('pk_opd.master_opd_id', $master_opd_id) 
                                ->where('pk_opd.tahun', $request->tahun) 
                                ->where('indikator_opd.is_active', true)
                                ->orderBy('indikator_opd.order', 'asc')
                                ->select('indikator_opd.id','indikator_opd.indikator', 'indikator_opd.order', 'indikator_opd.satuan_id')
                                ->distinct()
                                ->get(); 

            $anggaran_murni = DB::table('pk_opd_program')
            ->where('sasaran_opd_id', '=', $item->id)
            ->where('tahun', '=',$request->tahun)
            ->where('master_opd_id', $master_opd_id) 
            ->where('murni', '=',true)
            ->sum('anggaran');

            $anggaran_perubahan = DB::table('pk_opd_program')
            ->where('sasaran_opd_id', '=', $item->id)
            ->where('tahun', '=',$request->tahun)
            ->where('master_opd_id', $master_opd_id) 
            ->where('murni', '=',false)
            ->sum('anggaran');

            $indikator_sasaran = $indikator_sasaran->map(function($is) use ($request)
            {   
                $master_opd_id = $request->master_opd_id;

                $rencana_aksi = Rencana::where('indikator_opd_id', $is->id) 
                ->where('master_opd_id', $master_opd_id) 
                ->where('tahun', $request->tahun)
                ->select('id','target_tw1','target_tw2', 'target_tw3', 'target_tw4', 'realisasi_tw1', 'realisasi_tw2', 'realisasi_tw3','realisasi_tw4', 'capaian_tw1', 'capaian_tw2', 'capaian_tw3', 'capaian_tw4', 'hambatan', 'tindak_lanjut')  
                ->first();


                $langkah = RencanaLangkah::where('indikator_opd_id', '=', $is->id)
                               ->where('tahun', '=', $request->tahun)
                               ->where('master_opd_id', '=', $master_opd_id)
                               ->get();  

                $langkah = $langkah->map(function($dl) use($request) 
                {
                    return [
                        "id"               => $dl->id,
                        "langkah"          => $dl->langkah,
                        "tahun"            => $dl->tahun,
                        "target_tw1"       => $dl->target_tw1,
                        "target_tw2"       => $dl->target_tw2,
                        "target_tw3"       => $dl->target_tw3,
                        "target_tw4"       => $dl->target_tw4,
                        "realisasi_tw1"    => $dl->realisasi_tw1,
                        "realisasi_tw2"    => $dl->realisasi_tw2,
                        "realisasi_tw3"    => $dl->realisasi_tw3,
                        "realisasi_tw4"    => $dl->realisasi_tw4,
                        "capaian_tw1"      => $dl->capaian_tw1,
                        "capaian_tw2"      => $dl->capaian_tw2,
                        "capaian_tw3"      => $dl->capaian_tw3,
                        "capaian_tw4"      => $dl->capaian_tw4
                    ];
                });                

                $target_pk = DB::table('pk_opd')
                ->where('indikator_opd_id', '=', $is->id)
                ->where('tahun', '=',$request->tahun)
                ->where('master_opd_id', '=',$request->master_opd_id)
                ->get(['target', 'tahun', 'murni'])->first(); 
                
                return [
                    "id"                    => $is->id,
                    "indikator"             => $is->indikator,
                    "satuan"                => BaseController::getSatuanByID($is->satuan_id),
                    "order"                 => $is->order,
                    "rencana_aksi"          => $rencana_aksi,
                    "target_tw1"      => !empty($rencana_aksi) ? $rencana_aksi->target_tw1 : null,
                    "target_tw2"      => !empty($rencana_aksi) ? $rencana_aksi->target_tw2 : null,
                    "target_tw3"      => !empty($rencana_aksi) ? $rencana_aksi->target_tw3 : null,
                    "target_tw4"      => !empty($rencana_aksi) ? $rencana_aksi->target_tw4 : null,
                    "realisasi_tw1"   => !empty($rencana_aksi) ? $rencana_aksi->realisasi_tw1 : null,
                    "realisasi_tw2"   => !empty($rencana_aksi) ? $rencana_aksi->realisasi_tw2 : null,
                    "realisasi_tw3"   => !empty($rencana_aksi) ? $rencana_aksi->realisasi_tw3 : null,
                    "realisasi_tw4"   => !empty($rencana_aksi) ? $rencana_aksi->realisasi_tw4 : null,
                    "capaian_tw1"     => !empty($rencana_aksi) ? $rencana_aksi->capaian_tw1 : null,
                    "capaian_tw2"     => !empty($rencana_aksi) ? $rencana_aksi->capaian_tw2 : null,
                    "capaian_tw3"     => !empty($rencana_aksi) ? $rencana_aksi->capaian_tw3 : null,
                    "capaian_tw4"     => !empty($rencana_aksi) ? $rencana_aksi->capaian_tw4 : null,
                    "hambatan"        => !empty($rencana_aksi) ? $rencana_aksi->hambatan : null,
                    "tindak_lanjut"   => !empty($rencana_aksi) ? $rencana_aksi->tindak_lanjut : null,
                    "langkah"                   => $langkah,
                    "target_perjanjian_kinerja" => $target_pk
                ];                  
            }); 

            return [
                "id"                    => $item->id,
                "tujuan_opd_id"         => $item->tujuan_opd_id,
                "sasaran"               => $item->sasaran,
                "order"                 => $item->order,
                "indikator_sasaran"     => $indikator_sasaran,
                "anggaran_perjanjian_kinerja" => ['murni'=> $anggaran_murni, 'perubahan'=>$anggaran_perubahan],
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar Target & Realisasi Rencana Aksi  '.$opd->nama_opd.' Tahun '.$request->tahun.'  ',
            'data' => $sasaran,
        ]);
    }


}
