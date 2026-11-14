<?php

namespace App\Http\Controllers\Api\v1\Madani;

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

class AksiController extends Controller
{
    
    public function index(Request $request)
    {
        $simpeg_opd_id = $request->id_opd;
        $tahun = $request->tahun;
        $bulan = $request->bulan;

         // cek existing opd
         $opd = MasterOpd::where('simpeg_opd_id', '=', $simpeg_opd_id)->select('id', 'simpeg_opd_id', 'nama_opd')->first();
         if (!$opd) {
             // jika data tidak ditamukan di dalam database
             return response()->json([
                 'status' => false,
                 'label' => 'Perangkat Daerah not found.',
             ], 404);
         }

         //$biro = array('2876', '2863', '2809', '2796', '2836', '2889', '2823', '2849', '2783');
         //$master_opd_id = (in_array($simpeg_opd_id, $biro)) ? '36d2dee6-17b4-4ce8-87e0-01e097c36d6d' : $opd->id;
         $master_opd_id = $opd->id;

         $tujuan = BaseController::getTujuanOPD($master_opd_id);   
       
         $sasaran = SasaranOpd::whereIn('tujuan_opd_id', $tujuan)
                        ->where('parent_id', 0)
                        ->orderBy('order', 'ASC')
                        ->select('id')
                        ->pluck('id')->toArray();
        
        $indikator_sasaran = IndikatorOpd::whereIn('sasaran_opd_id', $sasaran) 
                            ->where('master_opd_id', $master_opd_id) 
                            ->orderBy('order', 'ASC')
                            ->select('indikator_opd.id','indikator_opd.indikator', 'indikator_opd.order')
                            ->distinct()
                            ->get();
                            
         $indikator_sasaran = $indikator_sasaran->map(function($is) use ($request, $master_opd_id, $tahun, $bulan)
         {   
                $langkah = RencanaLangkah::where('indikator_opd_id', '=', $is->id)
                               ->where('tahun', '=', $request->tahun)
                               ->where('master_opd_id', '=',$master_opd_id)
                               ->get();  

                $langkah = $langkah->map(function($dl) use($request) 
                {
                    return [
                        "id"            => $dl->id,
                        "langkah"       => $dl->langkah,
                        "satuan"       => $dl->satuan,
                        "keterangan"       => $dl->keterangan,
                        "tahun"       => $dl->tahun
                    ];
                });                
                
                return [
                    "id_sasaran_indikator" => $is->id,
                    "nm_sasaran_indikator" => $is->indikator,
                    "order" => $is->order,
                    "langkah_rencana_aksi" => $langkah,
                ];                  
            }); 

         return response()->json([
            'status' => true,
            'label' => 'Sasaran Strategis - Indikator Kinerja dan Rencana Aksi Pencapaian Target Indikator Kinerja  ',
            'opd' => $opd,
            'message' => array('indikator'=>$indikator_sasaran)
        ]);       
    }


}
