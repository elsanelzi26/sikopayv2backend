<?php

namespace App\Http\Controllers\Api\v1\KDH;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;

use App\Models\Sakip\KDH\PohonKinerjaSasaran;
use App\Models\Sakip\KDH\PohonKinerjaTujuan;
use App\Models\Sakip\KDH\PohonKinerjaIndikator;
use App\Models\Sakip\KDH\OpdPendukungIndikator;

use App\Models\Sakip\MASTER\MasterSatuan;
use App\Models\MASTER\MasterOpd;
use App\Http\Controllers\Api\v1\MASTER\BaseController;

use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
// Ditambahakan Elsa
use App\Models\Sakip\KDH\PerjanjianKinerja;
use App\Models\Sakip\KDH\PerjanjianKinerjaProgram;
use App\Models\Sakip\KDH\RencanaAksi;
use App\Models\Sakip\KDH\RencanaAksiLangkah;

// Tambahan untuk Excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanController extends Controller
{
    public function data_kinerja(Request $request)
    {
        try {

            $misi = BaseController::getMisiPemda();
            $tujuan = PohonKinerjaTujuan::whereIn('pohon_kinerja_misi_id', $misi)
            ->orderBy('order', 'asc')
            ->get();

            $tujuan = $tujuan->map(function ($item) use ($request) {

                $indikator_tujuan = PohonKinerjaIndikator::where('pohon_kinerja_tujuan_id', '=', $item->id)
                    ->where('is_tujuan', true)->get();

                $indikator_tujuan = $indikator_tujuan->map(function ($it) use ($request) {
                    $list =  [
                        "id" => $it->id,
                        "pohon_kinerja_tujuan_id" => $it->pohon_kinerja_tujuan_id,
                        "pohon_kinerja_sasaran_id" => $it->pohon_kinerja_sasaran_id,
                        "indikator" => $it->indikator,
                        "order" => $it->order,
                        // Dikomen dan diubah Elsa (penanganan salah view )
                        // Data Tahun N-1 harusnya data baseline
                        // "target_1" => $it->target_1,
                        // "target_2" => $it->target_2,
                        // "target_3" => $it->target_3,
                        // "target_4" => $it->target_4,
                        // "target_5" => $it->target_5,
                        // "target_6" => $it->target_6,
                        "target_1" => $it->baseline,
                        "target_2" => $it->target_1,
                        "target_3" => $it->target_2,
                        "target_4" => $it->target_3,
                        "target_5" => $it->target_4,
                        "target_6" => $it->target_5,
                        "realisasi_1" => $it->realisasi_1,
                        "realisasi_2" => $it->realisasi_2,
                        "realisasi_3" => $it->realisasi_3,
                        "realisasi_4" => $it->realisasi_4,
                        "realisasi_5" => $it->realisasi_5,
                        "realisasi_6" => $it->realisasi_6,
                        "capaian_1" => $it->capaian_1,
                        "capaian_2" => $it->capaian_2,
                        "capaian_3" => $it->capaian_3,
                        "capaian_4" => $it->capaian_4,
                        "capaian_5" => $it->capaian_5,
                        "capaian_6" => $it->capaian_6
                    ];
                    return $list;
                });

                $sasaran = PohonKinerjaSasaran::where('pohon_kinerja_tujuan_id', '=', $item->id)->get();

                $sasaran = $sasaran->map(function ($ds) use ($request) {

                    $sasaran_id = $ds->id;
                    $indikator_sasaran = PohonKinerjaIndikator::where('pohon_kinerja_sasaran_id', '=', $ds->id)
                        // Ditambahkan Elsa
                        ->where('is_active', true)
                        ->where('is_tujuan', false)->get();

                    $indikator_sasaran = $indikator_sasaran->map(function ($it) use ($request, $sasaran_id) {
                        $indikator_id = $it->id;
                        $visi = BaseController::getCurrentVisi();
                        $tahun_awal = $visi->period_starts;
                        $tahun_akhir = $visi->period_ends;


                        return [
                            "id" => $it->id,
                            "pohon_kinerja_tujuan_id" => $it->pohon_kinerja_tujuan_id,
                            "pohon_kinerja_sasaran_id" => $it->pohon_kinerja_sasaran_id,
                            "indikator" => $it->indikator,
                            "order" => $it->order,
                            // Dikomen dan diubah Elsa (penanganan salah view )
                            // Data Tahun N-1 harusnya data baseline
                            // "target_1" => $it->target_1,
                            // "target_2" => $it->target_2,
                            // "target_3" => $it->target_3,
                            // "target_4" => $it->target_4,
                            // "target_5" => $it->target_5,
                            // "target_6" => $it->target_6,
                            "target_1" => $it->baseline,
                            "target_2" => $it->target_1,
                            "target_3" => $it->target_2,
                            "target_4" => $it->target_3,
                            "target_5" => $it->target_4,
                            "target_6" => $it->target_5,
                            "realisasi_1" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal)['real'],
                            "realisasi_2" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 1)['real'],
                            "realisasi_3" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 2)['real'],
                            "realisasi_4" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 3)['real'],
                            "realisasi_5" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 4)['real'],
                            "realisasi_6" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 5)['real'],
                            "capaian_1" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal)['capai'],
                            "capaian_2" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 1)['capai'],
                            "capaian_3" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 2)['capai'],
                            "capaian_4" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 3)['capai'],
                            "capaian_5" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 4)['capai'],
                            "capaian_6" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 5)['capai']
                        ];
                    });

                    return  [
                        "id" => $ds->id,
                        "pohon_kinerja_tujuan_id" => $ds->pohon_kinerja_tujuan_id,
                        "sasaran" => $ds->sasaran,
                        "order" => $ds->order,
                        "indikator_sasaran" => $indikator_sasaran
                    ];
                });


                $list =  [
                    "id" => $item->id,
                    "pohon_kinerja_misi_id" => $item->pohon_kinerja_sasaran_id,
                    "tujuan" => $item->tujuan,
                    "order" => $item->order,
                    "indikator_tujuan" => $indikator_tujuan,
                    "sasaran" => $sasaran,
                ];
                return $list;
            });

            return response()->json([
                'success' => true,
                'message' => 'List of Target dan Realisasi RPJMD ',
                'data' => $tujuan
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'List of Target dan Realisasi RPJMD',
                'data' => [],
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function data_kinerja_cetak(Request $request)
    {
        try {

            $misi = BaseController::getMisiPemda();
            $tujuan = PohonKinerjaTujuan::whereIn('pohon_kinerja_misi_id', $misi)
            ->orderBy('order', 'asc')
            ->get();

            $tujuan = $tujuan->map(function ($item) use ($request) {

                $indikator_tujuan = PohonKinerjaIndikator::where('pohon_kinerja_tujuan_id', '=', $item->id)
                    // Ditambahkan filter oleh Elsa
                    ->where('is_active', true)->where('is_tujuan', true)->get();

                $indikator_tujuan = $indikator_tujuan->map(function ($it) use ($request) {
                    $list =  [
                        "id" => $it->id,
                        "pohon_kinerja_tujuan_id" => $it->pohon_kinerja_tujuan_id,
                        "pohon_kinerja_sasaran_id" => $it->pohon_kinerja_sasaran_id,
                        "indikator" => $it->indikator,
                        "order" => $it->order,
                        "target_1" => $it->target_1,
                        "target_2" => $it->target_2,
                        "target_3" => $it->target_3,
                        "target_4" => $it->target_4,
                        "target_5" => $it->target_5,
                        "target_6" => $it->target_6,
                        "realisasi_1" => $it->realisasi_1,
                        "realisasi_2" => $it->realisasi_2,
                        "realisasi_3" => $it->realisasi_3,
                        "realisasi_4" => $it->realisasi_4,
                        "realisasi_5" => $it->realisasi_5,
                        "realisasi_6" => $it->realisasi_6,
                        "capaian_1" => $it->capaian_1,
                        "capaian_2" => $it->capaian_2,
                        "capaian_3" => $it->capaian_3,
                        "capaian_4" => $it->capaian_4,
                        "capaian_5" => $it->capaian_5,
                        "capaian_6" => $it->capaian_6
                    ];
                    return $list;
                });

                $sasaran = PohonKinerjaSasaran::where('pohon_kinerja_tujuan_id', '=', $item->id)
                    // DItamabahkan Elsa
                    ->where('is_active', true)
                    ->get();

                $sasaran = $sasaran->map(function ($ds) use ($request) {

                    $sasaran_id = $ds->id;
                    $indikator_sasaran = PohonKinerjaIndikator::where('pohon_kinerja_sasaran_id', '=', $ds->id)
                        // DItamabahkan Elsa
                        ->where('is_active', true)
                        ->where('is_tujuan', false)->get();

                    $indikator_sasaran = $indikator_sasaran->map(function ($it) use ($request, $sasaran_id) {
                        $indikator_id = $it->id;
                        $visi = BaseController::getCurrentVisi();
                        $tahun_awal = $visi->period_starts;
                        $tahun_akhir = $visi->period_ends;


                        return [
                            "id" => $it->id,
                            "pohon_kinerja_tujuan_id" => $it->pohon_kinerja_tujuan_id,
                            "pohon_kinerja_sasaran_id" => $it->pohon_kinerja_sasaran_id,
                            "indikator" => $it->indikator,
                            "order" => $it->order,
                            "target_1" => $it->target_1,
                            "target_2" => $it->target_2,
                            "target_3" => $it->target_3,
                            "target_4" => $it->target_4,
                            "target_5" => $it->target_5,
                            "target_6" => $it->target_6,
                            "realisasi_1" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal)['real'],
                            "realisasi_2" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 1)['real'],
                            "realisasi_3" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 2)['real'],
                            "realisasi_4" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 3)['real'],
                            "realisasi_5" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 4)['real'],
                            "realisasi_6" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 5)['real'],
                            "capaian_1" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal)['capai'],
                            "capaian_2" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 1)['capai'],
                            "capaian_3" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 2)['capai'],
                            "capaian_4" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 3)['capai'],
                            "capaian_5" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 4)['capai'],
                            "capaian_6" => $this->get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal + 5)['capai']
                        ];
                    });

                    return  [
                        "id" => $ds->id,
                        "pohon_kinerja_tujuan_id" => $ds->pohon_kinerja_tujuan_id,
                        "sasaran" => $ds->sasaran,
                        "order" => $ds->order,
                        "indikator_sasaran" => $indikator_sasaran
                    ];
                });


                $list =  [
                    "id" => $item->id,
                    "pohon_kinerja_misi_id" => $item->pohon_kinerja_sasaran_id,
                    "tujuan" => $item->tujuan,
                    "order" => $item->order,
                    "indikator_tujuan" => $indikator_tujuan,
                    "sasaran" => $sasaran,
                ];
                return $list;
            });

            $data = [
                'generated_at' => now()->toDateTimeString(),
                'tujuan' => $tujuan,
                'visi' => BaseController::getCurrentVisi()
            ];
            $pdf = Pdf::loadView('report_template.kdh.kinerja', compact('data'))->setPaper('Legal', 'portrait');;
            return $pdf->download('Data Kinerja.pdf');
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'List of Target dan Realisasi RPJMD',
                'data' => [],
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    private function get_realisasi_pemda($sasaran_id, $indikator_id, $tahun_awal)
    {

        $realisasi = DB::table('rencana_aksi')
            ->where('pohon_kinerja_sasaran_id', '=', $sasaran_id)
            ->where('pohon_kinerja_indikator_id', '=', $indikator_id)
            ->where('tahun', '=', $tahun_awal)
            ->where('deleted_at', '=', NULL)
            ->first();

        return array(
            'real' => !empty($realisasi) ? $realisasi->realisasi_tw4 : '',
            'capai' => !empty($realisasi) ? $realisasi->capaian_tw4 : ''
        );
    }

    public function update_realisasi($id, Request $request)
    {
        try {
            // cek validasi jika id berformar uuid atau tidak
            if (!Str::isUuid($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Id, Indikator not Found',
                ], 422);
            }
            // cek existing indikator
            $indikator = PohonKinerjaIndikator::find($id);
            if (!$indikator) {
                return response()->json([
                    'success' => false,
                    'message' => 'indikator not found.',
                ], 404);
            }


            $form = $request->validate([
                "realisasi_1" => "required|string",
                "realisasi_2" => "required|string",
                "realisasi_3" => "required|string",
                "realisasi_4" => "required|string",
                "realisasi_5" => "required|string",
                "realisasi_6" => "required|string"
            ]);

            $target_1 = $indikator->target_1;
            $target_2 = $indikator->target_2;
            $target_3 = $indikator->target_3;
            $target_4 = $indikator->target_4;
            $target_5 = $indikator->target_5;
            $target_6 = $indikator->target_6;

            $realisasi_1 = $indikator->realisasi_1;
            $realisasi_2 = $indikator->realisasi_2;
            $realisasi_3 = $indikator->realisasi_3;
            $realisasi_4 = $indikator->realisasi_4;
            $realisasi_5 = $indikator->realisasi_5;
            $realisasi_6 = $indikator->realisasi_6;

            if ($target_1 == 0) $capaian_1 = 0;
            else $capaian_1 = $realisasi_1 / $target_1 * 100;
            if ($target_2 == 0) $capaian_1 = 0;
            else $capaian_2 = $realisasi_1 / $target_2 * 100;
            if ($target_3 == 0) $capaian_1 = 0;
            else $capaian_3 = $realisasi_1 / $target_3 * 100;
            if ($target_4 == 0) $capaian_1 = 0;
            else $capaian_4 = $realisasi_1 / $target_4 * 100;
            if ($target_5 == 0) $capaian_1 = 0;
            else $capaian_5 = $realisasi_1 / $target_5 * 100;
            if ($target_6 == 0) $capaian_1 = 0;
            else $capaian_6 = $realisasi_1 / $target_6 * 100;


            $form['realisasi_1'] = $request->realisasi_1;
            $form['realisasi_2'] = $request->realisasi_2;
            $form['realisasi_3'] = $request->realisasi_3;
            $form['realisasi_4'] = $request->realisasi_4;
            $form['realisasi_5'] = $request->realisasi_5;
            $form['realisasi_6'] = $request->realisasi_6;

            $form['updated_by'] = $request->get('payload')->username;

            $indikator->update($form);

            return response()->json([
                'success' => true,
                'message' => 'Realisasi RPJMD updated successfully.',
                'data' => $indikator,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'errors' => $th->getMessage()
            ], 500);
        }
    }


    public function capaian(Request $request)
    {
        try {

            $tahun = $request->tahun;
            $visi = BaseController::getCurrentVisi();

            $tahun_awal = $visi->period_starts;
            $tahun_akhir = $visi->period_ends;

            $periode = array();
            $key = 0;
            for ($x = $tahun_awal; $x <= $tahun_akhir; $x++) {
                $key++;
                $periode[$key] = $x;
            }

            if (!in_array($tahun, $periode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tahun tidak Sesuai dengan Periode Visi',
                ], 404);
            }



            $tahun_sekarang =  array_search($tahun, $periode);
            $tahun_lalu = $tahun_sekarang - 1;

            if (array_key_exists($tahun_lalu, $periode)) {
                $target_skrg     = "target_$tahun_sekarang";
                $realisasi_skrg     = "realisasi_$tahun_sekarang";
                $capaian_skrg     = "capaian_$tahun_sekarang";

                $target_lalu     = "target_$tahun_lalu";
                $realisasi_lalu     = "realisasi_$tahun_lalu";
                $capaian_lalu     = "capaian_$tahun_lalu";
            } else {
                $target_skrg = 0;
                $realisasi_skrg = 0;
                $capaian_skrg = 0;
                $target_lalu = 0;
                $realisasi_lalu = 0;
                $capaian_lalu = 0;
            }


            $misi = BaseController::getMisiPemda();
            $tujuan = PohonKinerjaTujuan::whereIn('pohon_kinerja_misi_id', $misi)
            ->orderBy('order', 'asc')
            ->get();

            $tujuan = $tujuan->map(function ($item) use ($request, $target_skrg, $realisasi_skrg, $capaian_skrg,  $target_lalu,  $realisasi_lalu,  $capaian_lalu,  $tahun_akhir) {

                $indikator_tujuan = PohonKinerjaIndikator::where('pohon_kinerja_tujuan_id', '=', $item->id)
                    // Ditambahkan Elsa
                    ->where('is_active', true)
                    ->where('is_tujuan', true)->get();

                $indikator_tujuan = $indikator_tujuan->map(function ($it) use ($request, $target_skrg, $realisasi_skrg, $capaian_skrg,  $target_lalu,  $realisasi_lalu,  $capaian_lalu) {

                    $list =  [
                        "id" => $it->id,
                        "pohon_kinerja_tujuan_id" => $it->pohon_kinerja_tujuan_id,
                        "pohon_kinerja_sasaran_id" => $it->pohon_kinerja_sasaran_id,
                        "indikator" => $it->indikator,
                        "order" => $it->order,
                        "target_tahun_sekarang" => $it->$target_skrg,
                        "realisasi_tahun_sekarang" => $it->$realisasi_skrg,
                        "capaian_tahun_sekarang" => $it->$capaian_skrg,

                        "target_tahun_lalu" => ($target_lalu != 0) ? $it->$target_lalu : "Target Tahun Lalu Not Found",
                        "realisasi_tahun_lalu" => ($target_lalu != 0) ? $it->$target_lalu : "Target Tahun Lalu Not Found",
                        "capaian_tahun_lalu" => ($target_lalu != 0) ? $it->$target_lalu : "Target Tahun Lalu Not Found",

                        "target_tahun_terakhir" => $it->target_6,
                        "realisasi_tahun_terakhir" => $it->realisasi_6,
                        "capaian_tahun_terakhir" => $it->capaian_6
                    ];
                    return $list;
                });

                $sasaran = PohonKinerjaSasaran::where('pohon_kinerja_tujuan_id', '=', $item->id)->get();

                $sasaran = $sasaran->map(function ($ds) use ($request,  $target_skrg, $realisasi_skrg, $capaian_skrg,  $target_lalu,  $realisasi_lalu,  $capaian_lalu,  $tahun_akhir) {

                    $sasaran_id = $ds->id;

                    $indikator_sasaran = PohonKinerjaIndikator::where('pohon_kinerja_sasaran_id', '=', $ds->id)
                        // Ditambahkan Elsa
                        ->where('is_active', true)
                        ->where('is_tujuan', false)->get();

                    $indikator_sasaran = $indikator_sasaran->map(function ($it) use ($request,  $target_skrg, $realisasi_skrg, $capaian_skrg,  $target_lalu,  $realisasi_lalu,  $capaian_lalu, $sasaran_id, $tahun_akhir) {

                        $indikator_id = $it->id;

                        $tahun_ini = date('Y');
                        $tahun_lalu = $tahun_ini - 1;

                        $realisasi_tahun_ini = DB::table('rencana_aksi')
                            ->where('pohon_kinerja_sasaran_id', '=', $sasaran_id)
                            ->where('pohon_kinerja_indikator_id', '=', $indikator_id)
                            ->where('tahun', '=', $tahun_ini)
                            ->where('deleted_at', '=', NULL)
                            ->first();

                        $realisasi_tahun_lalu = DB::table('rencana_aksi')
                            ->where('pohon_kinerja_sasaran_id', '=', $sasaran_id)
                            ->where('pohon_kinerja_indikator_id', '=', $indikator_id)
                            ->where('tahun', '=', $tahun_ini - 1)
                            ->where('deleted_at', '=', NULL)
                            ->first();

                        $realisasi_tahun_akhir = DB::table('rencana_aksi')
                            ->where('pohon_kinerja_sasaran_id', '=', $sasaran_id)
                            ->where('pohon_kinerja_indikator_id', '=', $indikator_id)
                            ->where('tahun', '=', $tahun_akhir)
                            ->where('deleted_at', '=', NULL)
                            ->first();

                        return [
                            "id" => $it->id,
                            "pohon_kinerja_tujuan_id" => $it->pohon_kinerja_tujuan_id,
                            "pohon_kinerja_sasaran_id" => $it->pohon_kinerja_sasaran_id,
                            "indikator" => $it->indikator,
                            "order" => $it->order,
                            "target_tahun_sekarang" => $it->$target_skrg,
                            "realisasi_tahun_sekarang" => !empty($realisasi_tahun_ini) ? $realisasi_tahun_ini->realisasi_tw4 : '',
                            "capaian_tahun_sekarang" => !empty($realisasi_tahun_ini) ? $realisasi_tahun_ini->capaian_tw4 : '',

                            "target_tahun_lalu" => ($target_lalu != 0) ? $it->$target_lalu : "Target Tahun Lalu Not Found",
                            "realisasi_tahun_lalu" => !empty($realisasi_tahun_lalu) ? $realisasi_tahun_lalu->capaian_tw4 : '',
                            "capaian_tahun_lalu" => !empty($realisasi_tahun_lalu) ? $realisasi_tahun_lalu->capaian_tw4 : '',

                            "target_tahun_terakhir" => $it->target_5,
                            "realisasi_tahun_terakhir" => !empty($realisasi_tahun_akhir) ? $realisasi_tahun_akhir->capaian_tw4 : '',
                            "capaian_tahun_terakhir" => !empty($realisasi_tahun_akhir) ? $realisasi_tahun_akhir->capaian_tw4 : ''
                        ];
                    });

                    return  [
                        "id" => $ds->id,
                        "pohon_kinerja_tujuan_id" => $ds->pohon_kinerja_tujuan_id,
                        "sasaran" => $ds->sasaran,
                        "order" => $ds->order,
                        "indikator_sasaran" => $indikator_sasaran
                    ];
                });


                $list =  [
                    "id" => $item->id,
                    "pohon_kinerja_misi_id" => $item->pohon_kinerja_sasaran_id,
                    "tujuan" => $item->tujuan,
                    "order" => $item->order,
                    "indikator_tujuan" => $indikator_tujuan,
                    "sasaran" => $sasaran,
                ];
                return $list;
            });

            return response()->json([
                'success' => true,
                'message' => 'List of Target dan Realisasi RPJMD ',
                'data' => $tujuan
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'List of Target dan Realisasi RPJMD',
                'data' => [],
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function capaian_cetak(Request $request)
    {
        try {

            $tahun = $request->tahun;
            $visi = BaseController::getCurrentVisi();

            $tahun_awal = $visi->period_starts;
            $tahun_akhir = $visi->period_ends;

            $periode = array();
            $key = 0;
            for ($x = $tahun_awal; $x <= $tahun_akhir; $x++) {
                $key++;
                $periode[$key] = $x;
            }

            if (!in_array($tahun, $periode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tahun tidak Sesuai dengan Periode Visi',
                ], 404);
            }



            $tahun_sekarang =  array_search($tahun, $periode);
            $tahun_lalu = $tahun_sekarang - 1;

            if (array_key_exists($tahun_lalu, $periode)) {
                $target_skrg     = "target_$tahun_sekarang";
                $realisasi_skrg     = "realisasi_$tahun_sekarang";
                $capaian_skrg     = "capaian_$tahun_sekarang";

                $target_lalu     = "target_$tahun_lalu";
                $realisasi_lalu     = "realisasi_$tahun_lalu";
                $capaian_lalu     = "capaian_$tahun_lalu";
            } else {
                $target_skrg = 0;
                $realisasi_skrg = 0;
                $capaian_skrg = 0;
                $target_lalu = 0;
                $realisasi_lalu = 0;
                $capaian_lalu = 0;
            }


            $misi = BaseController::getMisiPemda();
            $tujuan = PohonKinerjaTujuan::whereIn('pohon_kinerja_misi_id', $misi)
            ->orderBy('order', 'asc')
            ->get();

            $tujuan = $tujuan->map(function ($item) use ($request, $target_skrg, $realisasi_skrg, $capaian_skrg,  $target_lalu,  $realisasi_lalu,  $capaian_lalu,  $tahun_akhir) {

                $indikator_tujuan = PohonKinerjaIndikator::where('pohon_kinerja_tujuan_id', '=', $item->id)
                    // Ditambahkan filter oleh Elsa
                    ->where('is_active', true)
                    ->where('is_tujuan', true)->get();

                $indikator_tujuan = $indikator_tujuan->map(function ($it) use ($request, $target_skrg, $realisasi_skrg, $capaian_skrg,  $target_lalu,  $realisasi_lalu,  $capaian_lalu) {

                    $list =  [
                        "id" => $it->id,
                        "pohon_kinerja_tujuan_id" => $it->pohon_kinerja_tujuan_id,
                        "pohon_kinerja_sasaran_id" => $it->pohon_kinerja_sasaran_id,
                        "indikator" => $it->indikator,
                        "order" => $it->order,
                        "target_tahun_sekarang" => $it->$target_skrg,
                        "realisasi_tahun_sekarang" => $it->$realisasi_skrg,
                        "capaian_tahun_sekarang" => $it->$capaian_skrg,

                        "target_tahun_lalu" => ($target_lalu != 0) ? $it->$target_lalu : "Target Tahun Lalu Not Found",
                        "realisasi_tahun_lalu" => ($target_lalu != 0) ? $it->$target_lalu : "Target Tahun Lalu Not Found",
                        "capaian_tahun_lalu" => ($target_lalu != 0) ? $it->$target_lalu : "Target Tahun Lalu Not Found",

                        "target_tahun_terakhir" => $it->target_6,
                        "realisasi_tahun_terakhir" => $it->realisasi_6,
                        "capaian_tahun_terakhir" => $it->capaian_6
                    ];
                    return $list;
                });

                $sasaran = PohonKinerjaSasaran::where('pohon_kinerja_tujuan_id', '=', $item->id)
                    // Ditambahkan filter oleh Elsa
                    ->where('is_active', true)
                    ->get();

                $sasaran = $sasaran->map(function ($ds) use ($request,  $target_skrg, $realisasi_skrg, $capaian_skrg,  $target_lalu,  $realisasi_lalu,  $capaian_lalu,  $tahun_akhir) {

                    $sasaran_id = $ds->id;

                    $indikator_sasaran = PohonKinerjaIndikator::where('pohon_kinerja_sasaran_id', '=', $ds->id)
                        // Ditambahkan filter oleh Elsa
                        ->where('is_active', true)
                        ->where('is_tujuan', false)->get();

                    $indikator_sasaran = $indikator_sasaran->map(function ($it) use ($request,  $target_skrg, $realisasi_skrg, $capaian_skrg,  $target_lalu,  $realisasi_lalu,  $capaian_lalu, $sasaran_id, $tahun_akhir) {

                        $indikator_id = $it->id;

                        $tahun_ini = date('Y');
                        $tahun_lalu = $tahun_ini - 1;

                        $realisasi_tahun_ini = DB::table('rencana_aksi')
                            ->where('pohon_kinerja_sasaran_id', '=', $sasaran_id)
                            ->where('pohon_kinerja_indikator_id', '=', $indikator_id)
                            ->where('tahun', '=', $tahun_ini)
                            ->where('deleted_at', '=', NULL)
                            ->first();

                        $realisasi_tahun_lalu = DB::table('rencana_aksi')
                            ->where('pohon_kinerja_sasaran_id', '=', $sasaran_id)
                            ->where('pohon_kinerja_indikator_id', '=', $indikator_id)
                            ->where('tahun', '=', $tahun_ini - 1)
                            ->where('deleted_at', '=', NULL)
                            ->first();

                        $realisasi_tahun_akhir = DB::table('rencana_aksi')
                            ->where('pohon_kinerja_sasaran_id', '=', $sasaran_id)
                            ->where('pohon_kinerja_indikator_id', '=', $indikator_id)
                            ->where('tahun', '=', $tahun_akhir)
                            ->where('deleted_at', '=', NULL)
                            ->first();

                        return [
                            "id" => $it->id,
                            "pohon_kinerja_tujuan_id" => $it->pohon_kinerja_tujuan_id,
                            "pohon_kinerja_sasaran_id" => $it->pohon_kinerja_sasaran_id,
                            "indikator" => $it->indikator,
                            "order" => $it->order,
                            "target_tahun_sekarang" => $it->$target_skrg,
                            "realisasi_tahun_sekarang" => !empty($realisasi_tahun_ini) ? $realisasi_tahun_ini->realisasi_tw4 : '',
                            "capaian_tahun_sekarang" => !empty($realisasi_tahun_ini) ? $realisasi_tahun_ini->capaian_tw4 : '',

                            "target_tahun_lalu" => ($target_lalu != 0) ? $it->$target_lalu : "Target Tahun Lalu Not Found",
                            "realisasi_tahun_lalu" => !empty($realisasi_tahun_lalu) ? $realisasi_tahun_lalu->capaian_tw4 : '',
                            "capaian_tahun_lalu" => !empty($realisasi_tahun_lalu) ? $realisasi_tahun_lalu->capaian_tw4 : '',

                            "target_tahun_terakhir" => $it->target_5,
                            "realisasi_tahun_terakhir" => !empty($realisasi_tahun_akhir) ? $realisasi_tahun_akhir->capaian_tw4 : '',
                            "capaian_tahun_terakhir" => !empty($realisasi_tahun_akhir) ? $realisasi_tahun_akhir->capaian_tw4 : ''
                        ];
                    });

                    return  [
                        "id" => $ds->id,
                        "pohon_kinerja_tujuan_id" => $ds->pohon_kinerja_tujuan_id,
                        "sasaran" => $ds->sasaran,
                        "order" => $ds->order,
                        "indikator_sasaran" => $indikator_sasaran
                    ];
                });


                $list =  [
                    "id" => $item->id,
                    "pohon_kinerja_misi_id" => $item->pohon_kinerja_sasaran_id,
                    "tujuan" => $item->tujuan,
                    "order" => $item->order,
                    "indikator_tujuan" => $indikator_tujuan,
                    "sasaran" => $sasaran,
                ];
                return $list;
            });

            $data = [
                'generated_at' => now()->toDateTimeString(),
                'tujuan' => $tujuan,
                'tahun' => $tahun,
                'visi' => BaseController::getCurrentVisi()
            ];
            $pdf = Pdf::loadView('report_template.kdh.capaian', compact('data'))->setPaper('Legal', 'portrait');;
            return $pdf->download('Data Capaian.pdf');
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'List of Target dan Realisasi RPJMD',
                'data' => [],
                'errors' => $th->getMessage()
            ], 500);
        }
    }


    public function analisis(Request $request) {}

    public function efisiensi(Request $request) {}

    // Tambahan oleh Elsa
    public function cetak_analisis_kinerja(Request $request)
    {
        try {

            $tahun = $request->tahun;

            $searchColumn = collect(['sasaran']);
            $sasaran_id   = $request->get('sasaran_id');
            $indikator_id = $request->get('indikator_id');
            $search        = $request->get('search', '');

            $visi = BaseController::getCurrentVisi();
            $tujuan = BaseController::getTujuanPemda();

            $query = PohonKinerjaSasaran::query();

            if ($search != '') {
                $searchColumn->map(function ($item, $index) use ($search, $query) {

                    if ($index == 0) {
                        $query->where($item, 'like', "%{$search}%");
                    } else {
                        $query->orWhere($item, 'like', "%{$search}%");
                    }
                });
            }

            $query->whereIn('pohon_kinerja_tujuan_id', $tujuan);

            if ($sasaran_id) {
                $query->where('id', $sasaran_id);
            }

            $query->join(
                'pohon_kinerja_tujuan',
                'pohon_kinerja_tujuan.id',
                '=',
                'pohon_kinerja_sasaran.pohon_kinerja_tujuan_id'
            );

            $query->join(
                'pohon_kinerja_misi',
                'pohon_kinerja_misi.id',
                '=',
                'pohon_kinerja_tujuan.pohon_kinerja_misi_id'
            );

            $query->select('pohon_kinerja_sasaran.*');

            $query->orderBy('pohon_kinerja_misi.order');
            $query->orderBy('pohon_kinerja_tujuan.order');
            $query->orderBy('pohon_kinerja_sasaran.order');

            $sasaran = $query->get();

            $sasaran = $sasaran->map(function ($item) use ($tahun, $indikator_id) {

                $indikator = PohonKinerjaIndikator::where('is_tujuan', false)
                    ->where('pohon_kinerja_sasaran_id', $item->id)
                    ->where('is_active', true);

                if ($indikator_id) {
                    $indikator->where('id', $indikator_id);
                }

                $indikator = $indikator->get();

                $anggaran_murni = PerjanjianKinerjaProgram::where(
                    'pohon_kinerja_sasaran_id',
                    $item->id
                )
                    ->where('tahun', $tahun)
                    ->where('murni', true)
                    ->sum('anggaran');

                $anggaran_perubahan = PerjanjianKinerjaProgram::where(
                    'pohon_kinerja_sasaran_id',
                    $item->id
                )
                    ->where('tahun', $tahun)
                    ->where('murni', false)
                    ->sum('anggaran');

                $indikator = $indikator->map(function ($ik) use ($tahun) {

                    $rencanaAksi = RencanaAksi::where(
                        'pohon_kinerja_indikator_id',
                        $ik->id
                    )
                        ->where('tahun', $tahun)
                        ->first();

                    $langkah = RencanaAksiLangkah::where(
                        'pohon_kinerja_indikator_id',
                        $ik->id
                    )
                        ->where('tahun', $tahun)
                        ->get();

                    $targetPk = PerjanjianKinerja::where(
                        'pohon_kinerja_indikator_id',
                        $ik->id
                    )
                        ->where('tahun', $tahun)
                        ->get();

                    return [
                        'id' => $ik->id,
                        'indikator' => $ik->indikator,
                        'rencana_aksi' => $rencanaAksi,
                        'langkah' => $langkah,
                        'target_perjanjian_kinerja' => $targetPk,
                    ];
                });

                return [
                    'id' => $item->id,
                    'sasaran' => $item->sasaran,
                    'order' => $item->order,
                    'anggaran_perjanjian_kinerja' => [
                        'murni' => $anggaran_murni,
                        'perubahan' => $anggaran_perubahan,
                    ],
                    'indikator' => $indikator,
                ];
            });

            $data = [
                'generated_at' => now(),
                'tahun' => $tahun,
                'visi' => $visi,
                'sasaran' => $sasaran,
            ];

            $pdf = Pdf::loadView(
                'report_template.kdh.analisis_kinerja',
                compact('data')
            )->setPaper('Legal', 'landscape');

            return $pdf->download("Analisis_Kinerja_{$tahun}.pdf");
        } catch (\Throwable $th) {

            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function cetak_efisiensi_anggaran(Request $request)
    {
        try {

            $tahun = $request->tahun;

            $searchColumn = collect(['sasaran']);
            $sasaran_id   = $request->get('sasaran_id');
            $indikator_id = $request->get('indikator_id');
            $search        = $request->get('search', '');

            $visi = BaseController::getCurrentVisi();
            $tujuan = BaseController::getTujuanPemda();

            $query = PohonKinerjaSasaran::query();

            if ($search != '') {
                $searchColumn->map(function ($item, $index) use ($search, $query) {

                    if ($index == 0) {
                        $query->where($item, 'like', "%{$search}%");
                    } else {
                        $query->orWhere($item, 'like', "%{$search}%");
                    }
                });
            }

            $query->whereIn('pohon_kinerja_tujuan_id', $tujuan);

            if ($sasaran_id) {
                $query->where('id', $sasaran_id);
            }

            $query->join(
                'pohon_kinerja_tujuan',
                'pohon_kinerja_tujuan.id',
                '=',
                'pohon_kinerja_sasaran.pohon_kinerja_tujuan_id'
            );

            $query->join(
                'pohon_kinerja_misi',
                'pohon_kinerja_misi.id',
                '=',
                'pohon_kinerja_tujuan.pohon_kinerja_misi_id'
            );

            $query->select('pohon_kinerja_sasaran.*');

            $query->orderBy('pohon_kinerja_misi.order');
            $query->orderBy('pohon_kinerja_tujuan.order');
            $query->orderBy('pohon_kinerja_sasaran.order');

            $sasaran = $query->get();

            $sasaran = $sasaran->map(function ($item) use ($tahun, $indikator_id) {

                $indikator = PohonKinerjaIndikator::where('is_tujuan', false)
                    ->where('pohon_kinerja_sasaran_id', $item->id)
                    ->where('is_active', true);

                if ($indikator_id) {
                    $indikator->where('id', $indikator_id);
                }

                $indikator = $indikator->get();

                $anggaran_murni = PerjanjianKinerjaProgram::where(
                    'pohon_kinerja_sasaran_id',
                    $item->id
                )
                    ->where('tahun', $tahun)
                    ->where('murni', true)
                    ->sum('anggaran');

                $anggaran_perubahan = PerjanjianKinerjaProgram::where(
                    'pohon_kinerja_sasaran_id',
                    $item->id
                )
                    ->where('tahun', $tahun)
                    ->where('murni', false)
                    ->sum('anggaran');

                $indikator = $indikator->map(function ($ik) use ($tahun) {

                    $rencanaAksi = RencanaAksi::where(
                        'pohon_kinerja_indikator_id',
                        $ik->id
                    )
                        ->where('tahun', $tahun)
                        ->first();

                    $langkah = RencanaAksiLangkah::where(
                        'pohon_kinerja_indikator_id',
                        $ik->id
                    )
                        ->where('tahun', $tahun)
                        ->get();

                    $targetPk = PerjanjianKinerja::where(
                        'pohon_kinerja_indikator_id',
                        $ik->id
                    )
                        ->where('tahun', $tahun)
                        ->get();

                    return [
                        'id' => $ik->id,
                        'indikator' => $ik->indikator,
                        'rencana_aksi' => $rencanaAksi,
                        'langkah' => $langkah,
                        'target_perjanjian_kinerja' => $targetPk,
                    ];
                });

                return [
                    'id' => $item->id,
                    'sasaran' => $item->sasaran,
                    'order' => $item->order,
                    'anggaran_perjanjian_kinerja' => [
                        'murni' => $anggaran_murni,
                        'perubahan' => $anggaran_perubahan,
                    ],
                    'indikator' => $indikator,
                ];
            });

            $data = [
                'generated_at' => now(),
                'tahun' => $tahun,
                'visi' => $visi,
                'sasaran' => $sasaran,
            ];

            $pdf = Pdf::loadView(
                'report_template.kdh.efisiensi_anggaran',
                compact('data')
            )->setPaper('Legal', 'landscape');

            return $pdf->download("Efisiensi_Anggaran_{$tahun}.pdf");
        } catch (\Throwable $th) {

            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    // Tambahan Cetak Laporan Excel
    public function data_kinerja_export_excel(Request $request)
    {
        try {
            $misi = BaseController::getMisiPemda();

            $tujuan = PohonKinerjaTujuan::whereIn(
                'pohon_kinerja_misi_id',
                $misi
            )
            ->orderBy('order', 'asc')
            ->get();

            $tujuan = $tujuan->map(function ($item) use ($request) {

                $indikator_tujuan = PohonKinerjaIndikator::where(
                    'pohon_kinerja_tujuan_id',
                    $item->id
                )
                    ->where('is_active', true)
                    ->where('is_tujuan', true)
                    ->get();

                $indikator_tujuan = $indikator_tujuan->map(function ($it) {

                    return [
                        "id" => $it->id,
                        "pohon_kinerja_tujuan_id" => $it->pohon_kinerja_tujuan_id,
                        "pohon_kinerja_sasaran_id" => $it->pohon_kinerja_sasaran_id,
                        "indikator" => $it->indikator,
                        "order" => $it->order,

                        "target_1" => $it->target_1,
                        "target_2" => $it->target_2,
                        "target_3" => $it->target_3,
                        "target_4" => $it->target_4,
                        "target_5" => $it->target_5,
                        "target_6" => $it->target_6,

                        "realisasi_1" => $it->realisasi_1,
                        "realisasi_2" => $it->realisasi_2,
                        "realisasi_3" => $it->realisasi_3,
                        "realisasi_4" => $it->realisasi_4,
                        "realisasi_5" => $it->realisasi_5,
                        "realisasi_6" => $it->realisasi_6,

                        "capaian_1" => $it->capaian_1,
                        "capaian_2" => $it->capaian_2,
                        "capaian_3" => $it->capaian_3,
                        "capaian_4" => $it->capaian_4,
                        "capaian_5" => $it->capaian_5,
                        "capaian_6" => $it->capaian_6,
                    ];
                });


                $sasaran = PohonKinerjaSasaran::where(
                    'pohon_kinerja_tujuan_id',
                    $item->id
                )
                    ->where('is_active', true)
                    ->get();

                $sasaran = $sasaran->map(function ($ds) {

                    $sasaran_id = $ds->id;

                    $indikator_sasaran = PohonKinerjaIndikator::where(
                        'pohon_kinerja_sasaran_id',
                        $ds->id
                    )
                        ->where('is_active', true)
                        ->where('is_tujuan', false)
                        ->get();

                    $indikator_sasaran = $indikator_sasaran->map(
                        function ($it) use ($sasaran_id) {

                            $indikator_id = $it->id;

                            $visi = BaseController::getCurrentVisi();

                            $tahun_awal = $visi->period_starts;
                            $tahun_akhir = $visi->period_ends;

                            return [
                                "id" => $it->id,

                                "pohon_kinerja_tujuan_id" =>
                                $it->pohon_kinerja_tujuan_id,

                                "pohon_kinerja_sasaran_id" =>
                                $it->pohon_kinerja_sasaran_id,

                                "indikator" => $it->indikator,
                                "order" => $it->order,

                                "target_1" => $it->target_1,
                                "target_2" => $it->target_2,
                                "target_3" => $it->target_3,
                                "target_4" => $it->target_4,
                                "target_5" => $it->target_5,
                                "target_6" => $it->target_6,

                                "realisasi_1" =>
                                $this->get_realisasi_pemda(
                                    $sasaran_id,
                                    $indikator_id,
                                    $tahun_awal
                                )['real'],

                                "realisasi_2" =>
                                $this->get_realisasi_pemda(
                                    $sasaran_id,
                                    $indikator_id,
                                    $tahun_awal + 1
                                )['real'],

                                "realisasi_3" =>
                                $this->get_realisasi_pemda(
                                    $sasaran_id,
                                    $indikator_id,
                                    $tahun_awal + 2
                                )['real'],

                                "realisasi_4" =>
                                $this->get_realisasi_pemda(
                                    $sasaran_id,
                                    $indikator_id,
                                    $tahun_awal + 3
                                )['real'],

                                "realisasi_5" =>
                                $this->get_realisasi_pemda(
                                    $sasaran_id,
                                    $indikator_id,
                                    $tahun_awal + 4
                                )['real'],

                                "realisasi_6" =>
                                $this->get_realisasi_pemda(
                                    $sasaran_id,
                                    $indikator_id,
                                    $tahun_awal + 5
                                )['real'],

                                "capaian_1" =>
                                $this->get_realisasi_pemda(
                                    $sasaran_id,
                                    $indikator_id,
                                    $tahun_awal
                                )['capai'],

                                "capaian_2" =>
                                $this->get_realisasi_pemda(
                                    $sasaran_id,
                                    $indikator_id,
                                    $tahun_awal + 1
                                )['capai'],

                                "capaian_3" =>
                                $this->get_realisasi_pemda(
                                    $sasaran_id,
                                    $indikator_id,
                                    $tahun_awal + 2
                                )['capai'],

                                "capaian_4" =>
                                $this->get_realisasi_pemda(
                                    $sasaran_id,
                                    $indikator_id,
                                    $tahun_awal + 3
                                )['capai'],

                                "capaian_5" =>
                                $this->get_realisasi_pemda(
                                    $sasaran_id,
                                    $indikator_id,
                                    $tahun_awal + 4
                                )['capai'],

                                "capaian_6" =>
                                $this->get_realisasi_pemda(
                                    $sasaran_id,
                                    $indikator_id,
                                    $tahun_awal + 5
                                )['capai'],
                            ];
                        }
                    );

                    return [
                        "id" => $ds->id,

                        "pohon_kinerja_tujuan_id" =>
                        $ds->pohon_kinerja_tujuan_id,

                        "sasaran" => $ds->sasaran,
                        "order" => $ds->order,

                        "indikator_sasaran" => $indikator_sasaran
                    ];
                });

                return [
                    "id" => $item->id,

                    "pohon_kinerja_misi_id" =>
                    $item->pohon_kinerja_misi_id,

                    "tujuan" => $item->tujuan,
                    "order" => $item->order,

                    "indikator_tujuan" => $indikator_tujuan,
                    "sasaran" => $sasaran,
                ];
            });

            $visi = BaseController::getCurrentVisi();


            $spreadsheet = new Spreadsheet();

            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setTitle('Data Kinerja');

            $sheet->mergeCells('A1:R1');

            $sheet->setCellValue(
                'A1',
                'Data Kinerja Walikota'
            );

            $sheet->getStyle('A1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 16,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);


            $sheet->mergeCells('A2:R2');

            $sheet->setCellValue(
                'A2',
                'PERIODE : ' .
                    $visi->period_starts .
                    ' s/d ' .
                    $visi->period_ends
            );

            $sheet->getStyle('A2')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->mergeCells('A3:R3');

            $sheet->setCellValue(
                'A3',
                'Generated at: ' . now()->toDateTimeString()
            );

            // HEADER
            $sheet->mergeCells('A5:A7');
            $sheet->mergeCells('B5:B7');
            $sheet->mergeCells('C5:C7');

            $sheet->setCellValue('A5', 'No');
            $sheet->setCellValue('B5', 'Tujuan/Sasaran');
            $sheet->setCellValue('C5', 'Indikator');

            // Tahun 1
            $sheet->mergeCells('D5:F5');
            $sheet->setCellValue('D5', 'Tahun 1');

            // Tahun 2
            $sheet->mergeCells('G5:I5');
            $sheet->setCellValue('G5', 'Tahun 2');

            // Tahun 3
            $sheet->mergeCells('J5:L5');
            $sheet->setCellValue('J5', 'Tahun 3');

            // Tahun 4
            $sheet->mergeCells('M5:O5');
            $sheet->setCellValue('M5', 'Tahun 4');

            // Tahun 5
            $sheet->mergeCells('P5:R5');
            $sheet->setCellValue('P5', 'Tahun 5');

            // SUB HEADER

            $subHeader = [

                'D6' => 'T',
                'E6' => 'R',
                'F6' => 'C',

                'G6' => 'T',
                'H6' => 'R',
                'I6' => 'C',

                'J6' => 'T',
                'K6' => 'R',
                'L6' => 'C',

                'M6' => 'T',
                'N6' => 'R',
                'O6' => 'C',

                'P6' => 'T',
                'Q6' => 'R',
                'R6' => 'C',
            ];

            foreach ($subHeader as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }

            // Merge sub header
            $sheet->mergeCells('D6:D7');
            $sheet->mergeCells('E6:E7');
            $sheet->mergeCells('F6:F7');

            $sheet->mergeCells('G6:G7');
            $sheet->mergeCells('H6:H7');
            $sheet->mergeCells('I6:I7');

            $sheet->mergeCells('J6:J7');
            $sheet->mergeCells('K6:K7');
            $sheet->mergeCells('L6:L7');

            $sheet->mergeCells('M6:M7');
            $sheet->mergeCells('N6:N7');
            $sheet->mergeCells('O6:O7');

            $sheet->mergeCells('P6:P7');
            $sheet->mergeCells('Q6:Q7');
            $sheet->mergeCells('R6:R7');

            // STYLE HEADER

            $sheet->getStyle('A5:R7')->applyFromArray([
                'font' => [
                    'bold' => true,
                ],

                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'F5F5F5'
                    ],
                ],

                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],

                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => [
                            'rgb' => '999999'
                        ],
                    ],
                ],
            ]);

            // DATA

            $row = 8;

            foreach ($tujuan as $tujuanItem) {

                // TUJUAN

                $sheet->mergeCells(
                    "A{$row}:R{$row}"
                );

                $sheet->setCellValue(
                    "A{$row}",
                    $tujuanItem['tujuan']
                );

                $sheet->getStyle(
                    "A{$row}:R{$row}"
                )->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],

                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'E7E6E6'
                        ],
                    ],

                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],

                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => [
                                'rgb' => '999999'
                            ],
                        ],
                    ],
                ]);

                $row++;

                // 2. INDIKATOR TUJUAN (DITAMBAHKAN DI SINI)

                if (!empty($tujuanItem['indikator_tujuan'])) {
                    foreach ($tujuanItem['indikator_tujuan'] as $it) {
                        // Kolom A & B kosong / di-merge untuk Indikator Tujuan
                        $sheet->setCellValue("C{$row}", "Indikator : " . $it['indikator']);

                        // Target, Realisasi, Capaian Indikator Tujuan
                        $sheet->setCellValue("D{$row}", $it['target_1'] ?? '');
                        $sheet->setCellValue("E{$row}", $it['realisasi_1'] ?? '');
                        $sheet->setCellValue("F{$row}", $it['capaian_1'] ?? '');

                        $sheet->setCellValue("G{$row}", $it['target_2'] ?? '');
                        $sheet->setCellValue("H{$row}", $it['realisasi_2'] ?? '');
                        $sheet->setCellValue("I{$row}", $it['capaian_2'] ?? '');

                        $sheet->setCellValue("J{$row}", $it['target_3'] ?? '');
                        $sheet->setCellValue("K{$row}", $it['realisasi_3'] ?? '');
                        $sheet->setCellValue("L{$row}", $it['capaian_3'] ?? '');

                        $sheet->setCellValue("M{$row}", $it['target_4'] ?? '');
                        $sheet->setCellValue("N{$row}", $it['realisasi_4'] ?? '');
                        $sheet->setCellValue("O{$row}", $it['capaian_4'] ?? '');

                        $sheet->setCellValue("P{$row}", $it['target_5'] ?? '');
                        $sheet->setCellValue("Q{$row}", $it['realisasi_5'] ?? '');
                        $sheet->setCellValue("R{$row}", $it['capaian_5'] ?? '');

                        // Optional: beri style bercetak miring / italic untuk membedakan dengan indikator sasaran
                        $sheet->getStyle("C{$row}")->getFont()->setItalic(true);

                        $row++;
                    }
                }

                // ==========================================
                // SASARAN
                // ==========================================

                $i = 1;

                foreach ($tujuanItem['sasaran'] as $node) {

                    $indikator = $node['indikator_sasaran'];

                    // ==========================================
                    // JIKA TIDAK ADA INDIKATOR
                    // ==========================================

                    if ($indikator->count() == 0) {

                        $sheet->setCellValue(
                            "A{$row}",
                            $i
                        );

                        $sheet->setCellValue(
                            "B{$row}",
                            $node['sasaran']
                        );

                        $sheet->setCellValue(
                            "C{$row}",
                            'No data found'
                        );

                        $row++;

                        $i++;

                        continue;
                    }

                    $startRowSasaran = $row;

                    $x = 1;

                    // ==========================================
                    // INDIKATOR SASARAN
                    // ==========================================

                    foreach ($indikator as $di) {

                        $sheet->setCellValue(
                            "A{$row}",
                            $i
                        );

                        $sheet->setCellValue(
                            "B{$row}",
                            $node['sasaran']
                        );

                        $sheet->setCellValue(
                            "C{$row}",
                            $x . '. ' . $di['indikator']
                        );

                        // ==========================================
                        // TAHUN 1
                        // ==========================================

                        $sheet->setCellValue(
                            "D{$row}",
                            $di['target_1'] ?? ''
                        );

                        $sheet->setCellValue(
                            "E{$row}",
                            $di['realisasi_1'] ?? ''
                        );

                        $sheet->setCellValue(
                            "F{$row}",
                            $di['capaian_1'] ?? ''
                        );

                        // ==========================================
                        // TAHUN 2
                        // ==========================================

                        $sheet->setCellValue(
                            "G{$row}",
                            $di['target_2'] ?? ''
                        );

                        $sheet->setCellValue(
                            "H{$row}",
                            $di['realisasi_2'] ?? ''
                        );

                        $sheet->setCellValue(
                            "I{$row}",
                            $di['capaian_2'] ?? ''
                        );

                        // ==========================================
                        // TAHUN 3
                        // ==========================================

                        $sheet->setCellValue(
                            "J{$row}",
                            $di['target_3'] ?? ''
                        );

                        $sheet->setCellValue(
                            "K{$row}",
                            $di['realisasi_3'] ?? ''
                        );

                        $sheet->setCellValue(
                            "L{$row}",
                            $di['capaian_3'] ?? ''
                        );

                        // ==========================================
                        // TAHUN 4
                        // ==========================================

                        $sheet->setCellValue(
                            "M{$row}",
                            $di['target_4'] ?? ''
                        );

                        $sheet->setCellValue(
                            "N{$row}",
                            $di['realisasi_4'] ?? ''
                        );

                        $sheet->setCellValue(
                            "O{$row}",
                            $di['capaian_4'] ?? ''
                        );

                        // ==========================================
                        // TAHUN 5
                        // ==========================================

                        $sheet->setCellValue(
                            "P{$row}",
                            $di['target_5'] ?? ''
                        );

                        $sheet->setCellValue(
                            "Q{$row}",
                            $di['realisasi_5'] ?? ''
                        );

                        $sheet->setCellValue(
                            "R{$row}",
                            $di['capaian_5'] ?? ''
                        );

                        $row++;

                        $x++;
                    }

                    // ==========================================
                    // MERGE NOMOR DAN SASARAN
                    // ==========================================

                    $endRowSasaran = $row - 1;

                    if ($endRowSasaran > $startRowSasaran) {

                        $sheet->mergeCells(
                            "A{$startRowSasaran}:A{$endRowSasaran}"
                        );

                        $sheet->mergeCells(
                            "B{$startRowSasaran}:B{$endRowSasaran}"
                        );
                    }

                    $i++;
                }
            }

            // ==========================================
            // BORDER DATA
            // ==========================================

            if ($row > 8) {

                $sheet->getStyle(
                    "A8:R" . ($row - 1)
                )->applyFromArray([

                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => [
                                'rgb' => 'CCCCCC'
                            ],
                        ],
                    ],

                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_TOP,
                        'wrapText' => true,
                    ],
                ]);
            }

            // ==========================================
            // LEBAR KOLOM
            // ==========================================

            $sheet->getColumnDimension('A')->setWidth(8);
            $sheet->getColumnDimension('B')->setWidth(35);
            $sheet->getColumnDimension('C')->setWidth(45);

            foreach (range('D', 'R') as $column) {
                $sheet->getColumnDimension($column)->setWidth(15);
            }

            // ==========================================
            // TINGGI BARIS
            // ==========================================

            $sheet->getRowDimension(1)->setRowHeight(25);
            $sheet->getRowDimension(2)->setRowHeight(22);

            for ($i = 5; $i <= 7; $i++) {
                $sheet->getRowDimension($i)->setRowHeight(25);
            }

            // ==========================================
            // FREEZE HEADER
            // ==========================================

            $sheet->freezePane('D8');

            // ==========================================
            // PAGE SETUP
            // ==========================================

            $sheet->getPageSetup()
                ->setOrientation(
                    \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                );

            $sheet->getPageSetup()
                ->setPaperSize(
                    \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LEGAL
                );

            $sheet->getPageSetup()->setFitToWidth(1);
            $sheet->getPageSetup()->setFitToHeight(0);

            $sheet->getPageMargins()->setTop(0.3);
            $sheet->getPageMargins()->setRight(0.3);
            $sheet->getPageMargins()->setLeft(0.3);
            $sheet->getPageMargins()->setBottom(0.3);

            // ==========================================
            // SIMPAN FILE EXCEL
            // ==========================================

            $filename = 'Laporan_Rencana_Kerja_Walikota.xlsx';

            $filePath = storage_path(
                'app/public/' . $filename
            );

            $writer = new Xlsx($spreadsheet);

            $writer->save($filePath);

            // ==========================================
            // DOWNLOAD BIASA
            // ==========================================

            return response()->download(
                $filePath,
                $filename,
                [
                    'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            )->deleteFileAfterSend(true);
        } catch (\Throwable $th) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal export Data Kinerja',
                'data' => [],
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function capaian_kinerja_export_excel(Request $request)
    {
        try {
            $tahun = $request->tahun;
            $visi = BaseController::getCurrentVisi();

            $tahun_awal = $visi->period_starts;
            $tahun_akhir = $visi->period_ends;

            $periode = array();
            $key = 0;
            for ($x = $tahun_awal; $x <= $tahun_akhir; $x++) {
                $key++;
                $periode[$key] = $x;
            }

            if (!in_array($tahun, $periode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tahun tidak Sesuai dengan Periode Visi',
                ], 404);
            }

            $tahun_sekarang = array_search($tahun, $periode);
            $tahun_lalu = $tahun_sekarang - 1;

            if (array_key_exists($tahun_lalu, $periode)) {
                $target_skrg    = "target_$tahun_sekarang";
                $realisasi_skrg = "realisasi_$tahun_sekarang";
                $capaian_skrg   = "capaian_$tahun_sekarang";

                $target_lalu    = "target_$tahun_lalu";
                $realisasi_lalu = "realisasi_$tahun_lalu";
                $capaian_lalu   = "capaian_$tahun_lalu";
            } else {
                $target_skrg = 0;
                $realisasi_skrg = 0;
                $capaian_skrg = 0;
                $target_lalu = 0;
                $realisasi_lalu = 0;
                $capaian_lalu = 0;
            }

            $misi = BaseController::getMisiPemda();
            $tujuan = PohonKinerjaTujuan::whereIn('pohon_kinerja_misi_id', $misi)
            ->orderBy('order', 'asc')
            ->get();

            $tujuan = $tujuan->map(function ($item) use ($request, $target_skrg, $realisasi_skrg, $capaian_skrg, $target_lalu, $realisasi_lalu, $capaian_lalu, $tahun_akhir) {

                $indikator_tujuan = PohonKinerjaIndikator::where('pohon_kinerja_tujuan_id', '=', $item->id)
                    ->where('is_active', true)
                    ->where('is_tujuan', true)->get();

                $indikator_tujuan = $indikator_tujuan->map(function ($it) use ($request, $target_skrg, $realisasi_skrg, $capaian_skrg, $target_lalu, $realisasi_lalu, $capaian_lalu) {

                    return [
                        "id" => $it->id,
                        "pohon_kinerja_tujuan_id" => $it->pohon_kinerja_tujuan_id,
                        "pohon_kinerja_sasaran_id" => $it->pohon_kinerja_sasaran_id,
                        "indikator" => $it->indikator,
                        "order" => $it->order,
                        "target_tahun_sekarang" => $it->$target_skrg,
                        "realisasi_tahun_sekarang" => $it->$realisasi_skrg,
                        "capaian_tahun_sekarang" => $it->$capaian_skrg,

                        "target_tahun_lalu" => ($target_lalu != 0) ? $it->$target_lalu : "",
                        "realisasi_tahun_lalu" => ($target_lalu != 0) ? $it->$target_lalu : "",
                        "capaian_tahun_lalu" => ($target_lalu != 0) ? $it->$target_lalu : "",

                        "target_tahun_terakhir" => $it->target_6,
                        "realisasi_tahun_terakhir" => $it->realisasi_6,
                        "capaian_tahun_terakhir" => $it->capaian_6
                    ];
                });

                $sasaran = PohonKinerjaSasaran::where('pohon_kinerja_tujuan_id', '=', $item->id)
                    ->where('is_active', true)
                    ->get();

                $sasaran = $sasaran->map(function ($ds) use ($request, $target_skrg, $realisasi_skrg, $capaian_skrg, $target_lalu, $realisasi_lalu, $capaian_lalu, $tahun_akhir) {

                    $sasaran_id = $ds->id;

                    $indikator_sasaran = PohonKinerjaIndikator::where('pohon_kinerja_sasaran_id', '=', $ds->id)
                        ->where('is_active', true)
                        ->where('is_tujuan', false)->get();

                    $indikator_sasaran = $indikator_sasaran->map(function ($it) use ($request, $target_skrg, $realisasi_skrg, $capaian_skrg, $target_lalu, $realisasi_lalu, $capaian_lalu, $sasaran_id, $tahun_akhir) {

                        $indikator_id = $it->id;

                        $tahun_ini = date('Y');

                        $realisasi_tahun_ini = DB::table('rencana_aksi')
                            ->where('pohon_kinerja_sasaran_id', '=', $sasaran_id)
                            ->where('pohon_kinerja_indikator_id', '=', $indikator_id)
                            ->where('tahun', '=', $tahun_ini)
                            ->where('deleted_at', '=', NULL)
                            ->first();

                        $realisasi_tahun_lalu = DB::table('rencana_aksi')
                            ->where('pohon_kinerja_sasaran_id', '=', $sasaran_id)
                            ->where('pohon_kinerja_indikator_id', '=', $indikator_id)
                            ->where('tahun', '=', $tahun_ini - 1)
                            ->where('deleted_at', '=', NULL)
                            ->first();

                        $realisasi_tahun_akhir = DB::table('rencana_aksi')
                            ->where('pohon_kinerja_sasaran_id', '=', $sasaran_id)
                            ->where('pohon_kinerja_indikator_id', '=', $indikator_id)
                            ->where('tahun', '=', $tahun_akhir)
                            ->where('deleted_at', '=', NULL)
                            ->first();

                        return [
                            "id" => $it->id,
                            "pohon_kinerja_tujuan_id" => $it->pohon_kinerja_tujuan_id,
                            "pohon_kinerja_sasaran_id" => $it->pohon_kinerja_sasaran_id,
                            "indikator" => $it->indikator,
                            "order" => $it->order,
                            "target_tahun_sekarang" => $it->$target_skrg,
                            "realisasi_tahun_sekarang" => !empty($realisasi_tahun_ini) ? $realisasi_tahun_ini->realisasi_tw4 : '',
                            "capaian_tahun_sekarang" => !empty($realisasi_tahun_ini) ? $realisasi_tahun_ini->capaian_tw4 : '',

                            "target_tahun_lalu" => ($target_lalu != 0) ? $it->$target_lalu : "",
                            "realisasi_tahun_lalu" => !empty($realisasi_tahun_lalu) ? $realisasi_tahun_lalu->capaian_tw4 : '',
                            "capaian_tahun_lalu" => !empty($realisasi_tahun_lalu) ? $realisasi_tahun_lalu->capaian_tw4 : '',

                            "target_tahun_terakhir" => $it->target_5,
                            "realisasi_tahun_terakhir" => !empty($realisasi_tahun_akhir) ? $realisasi_tahun_akhir->capaian_tw4 : '',
                            "capaian_tahun_terakhir" => !empty($realisasi_tahun_akhir) ? $realisasi_tahun_akhir->capaian_tw4 : ''
                        ];
                    });

                    return [
                        "id" => $ds->id,
                        "pohon_kinerja_tujuan_id" => $ds->pohon_kinerja_tujuan_id,
                        "sasaran" => $ds->sasaran,
                        "order" => $ds->order,
                        "indikator_sasaran" => $indikator_sasaran
                    ];
                });

                return [
                    "id" => $item->id,
                    "pohon_kinerja_misi_id" => $item->pohon_kinerja_sasaran_id,
                    "tujuan" => $item->tujuan,
                    "order" => $item->order,
                    "indikator_tujuan" => $indikator_tujuan,
                    "sasaran" => $sasaran,
                ];
            });

            // ==========================================
            // INISIALISASI SPREADSHEET
            // ==========================================
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data Capaian');

            // ==========================================
            // JUDUL & METADATA
            // ==========================================
            $sheet->mergeCells('A1:Q1');
            $sheet->setCellValue('A1', 'DATA CAPAIAN KINERJA WALIKOTA');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            $sheet->mergeCells('A2:Q2');
            $sheet->setCellValue('A2', 'PERIODE : ' . $visi->period_starts . ' s/d ' . $visi->period_ends);
            $sheet->getStyle('A2')->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            $sheet->mergeCells('A3:Q3');
            $sheet->setCellValue('A3', 'Generated at: ' . now()->toDateTimeString());

            // ==========================================
            // HEADER TABLE (3 TINGKAT)
            // ==========================================
            // Row 5, 6, 7
            $sheet->mergeCells('A5:A7')->setCellValue('A5', 'No');
            $sheet->mergeCells('B5:B7')->setCellValue('B5', 'Sasaran');
            $sheet->mergeCells('C5:C7')->setCellValue('C5', 'Indikator');

            // Group 1: Capaian Kinerja Tahun n
            $sheet->mergeCells('D5:F5')->setCellValue('D5', 'Capaian Kinerja Tahun n');
            $sheet->mergeCells('D6:D7')->setCellValue('D6', 'Target');
            $sheet->mergeCells('E6:E7')->setCellValue('E6', 'Realisasi');
            $sheet->mergeCells('F6:F7')->setCellValue('F6', 'Capaian');

            // Group 2: Peningkatan dari tahun lalu (n-1)
            $sheet->mergeCells('G5:J5')->setCellValue('G5', 'Peningkatan dari tahun lalu (n-1)');
            $sheet->mergeCells('G6:G7')->setCellValue('G6', "Realisasi\n(n-1)");
            $sheet->mergeCells('H6:H7')->setCellValue('H6', "Capaian\n(n-1)");
            $sheet->mergeCells('I6:J6')->setCellValue('I6', 'Perbandingan dengan tahun lalu');
            $sheet->setCellValue('I7', 'Realisasi');
            $sheet->setCellValue('J7', 'Capaian');

            // Group 3: Perbandingan dengan tahun Terakhir RPJMD
            $sheet->mergeCells('K5:M5')->setCellValue('K5', 'Perbandingan dengan tahun Terakhir RPJMD');
            $sheet->mergeCells('K6:K7')->setCellValue('K6', "Target Tahun\nTerakhir RPJMD");
            $sheet->mergeCells('L6:L7')->setCellValue('L6', "Realisasi\ntahun n");
            $sheet->mergeCells('M6:M7')->setCellValue('M6', 'Selisih');

            // Group 4: Perbandingan dengan Nasional
            $sheet->mergeCells('N5:P5')->setCellValue('N5', 'Perbandingan dengan Nasional');
            $sheet->mergeCells('N6:N7')->setCellValue('N6', "Rata-rata\nNasional");
            $sheet->mergeCells('O6:O7')->setCellValue('O6', "Realisasi\ntahun n");
            $sheet->mergeCells('P6:P7')->setCellValue('P6', "Peringkat\nNasional");

            // Style Header
            $sheet->getStyle('A5:P7')->applyFromArray([
                'font' => ['bold' => true, 'size' => 9],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F5F5F5']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '999999']
                    ],
                ],
            ]);

            // ==========================================
            // DATA BODY
            // ==========================================
            $row = 8;

            foreach ($tujuan as $tujuanItem) {

                // --- 1. BARIS TUJUAN ---
                $sheet->mergeCells("A{$row}:P{$row}");
                $sheet->setCellValue("A{$row}", $tujuanItem['tujuan']);
                $sheet->getStyle("A{$row}:P{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E7E6E6']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '999999']
                        ],
                    ],
                ]);
                $row++;

                // --- 2. BARIS INDIKATOR TUJUAN ---
                if (!empty($tujuanItem['indikator_tujuan'])) {
                    foreach ($tujuanItem['indikator_tujuan'] as $it) {
                        $sheet->setCellValue("C{$row}", "Indikator : " . $it['indikator']);
                        $sheet->setCellValue("D{$row}", $it['target_tahun_sekarang'] ?? '');
                        $sheet->setCellValue("E{$row}", $it['realisasi_tahun_sekarang'] ?? '');
                        $sheet->setCellValue("F{$row}", $it['capaian_tahun_sekarang'] ?? '');

                        $sheet->setCellValue("G{$row}", $it['realisasi_tahun_lalu'] ?? '');
                        $sheet->setCellValue("H{$row}", $it['capaian_tahun_lalu'] ?? '');
                        $sheet->setCellValue("I{$row}", '');
                        $sheet->setCellValue("J{$row}", '');

                        $sheet->setCellValue("K{$row}", $it['target_tahun_terakhir'] ?? '');
                        $sheet->setCellValue("L{$row}", $it['realisasi_tahun_sekarang'] ?? '');
                        $sheet->setCellValue("M{$row}", '');

                        $sheet->setCellValue("N{$row}", '-');
                        $sheet->setCellValue("O{$row}", '-');
                        $sheet->setCellValue("P{$row}", '-');

                        $sheet->getStyle("C{$row}")->getFont()->setItalic(true);
                        $row++;
                    }
                }

                // --- 3. BARIS SASARAN & INDIKATOR SASARAN ---
                $i = 1;
                foreach ($tujuanItem['sasaran'] as $node) {
                    $indikatorList = $node['indikator_sasaran'];

                    if ($indikatorList->count() == 0) {
                        $sheet->setCellValue("A{$row}", $i);
                        $sheet->setCellValue("B{$row}", $node['sasaran']);
                        $sheet->setCellValue("C{$row}", 'No data found');
                        $row++;
                        $i++;
                        continue;
                    }

                    $startRowSasaran = $row;
                    $x = 1;

                    foreach ($indikatorList as $di) {
                        $sheet->setCellValue("A{$row}", $i);
                        $sheet->setCellValue("B{$row}", $node['sasaran']);
                        $sheet->setCellValue("C{$row}", $x . '. ' . $di['indikator']);

                        $sheet->setCellValue("D{$row}", $di['target_tahun_sekarang'] ?? '');
                        $sheet->setCellValue("E{$row}", $di['realisasi_tahun_sekarang'] ?? '');
                        $sheet->setCellValue("F{$row}", $di['capaian_tahun_sekarang'] ?? '');

                        $sheet->setCellValue("G{$row}", $di['realisasi_tahun_lalu'] ?? '');
                        $sheet->setCellValue("H{$row}", $di['capaian_tahun_lalu'] ?? '');
                        $sheet->setCellValue("I{$row}", '');
                        $sheet->setCellValue("J{$row}", '');

                        $sheet->setCellValue("K{$row}", $di['target_tahun_terakhir'] ?? '');
                        $sheet->setCellValue("L{$row}", $di['realisasi_tahun_sekarang'] ?? '');
                        $sheet->setCellValue("M{$row}", '');

                        $sheet->setCellValue("N{$row}", '-');
                        $sheet->setCellValue("O{$row}", '-');
                        $sheet->setCellValue("P{$row}", '-');

                        $row++;
                        $x++;
                    }

                    $endRowSasaran = $row - 1;
                    if ($endRowSasaran > $startRowSasaran) {
                        $sheet->mergeCells("A{$startRowSasaran}:A{$endRowSasaran}");
                        $sheet->mergeCells("B{$startRowSasaran}:B{$endRowSasaran}");
                    }

                    $i++;
                }
            }

            // ==========================================
            // BORDER & STYLING DATA
            // ==========================================
            if ($row > 8) {
                $sheet->getStyle("A8:P" . ($row - 1))->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC']
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_TOP, 
                        'wrapText' => true,
                    ],
                ]);
            }

            // Set Lebar Kolom
            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getColumnDimension('B')->setWidth(30);
            $sheet->getColumnDimension('C')->setWidth(35);
            foreach (range('D', 'P') as $col) {
                $sheet->getColumnDimension($col)->setWidth(14);
            }

            // Freeze Panes & Page Setup
            $sheet->freezePane('D8');
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LEGAL);

            // ==========================================
            // EXPORT STREAM / DOWNLOAD
            // ==========================================
            $filename = 'Data Capaian Kinerja Walikota.xlsx';
            $filePath = storage_path('app/public/' . $filename);

            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal export Data Capaian Kinerja',
                'data' => [],
                'errors' => $th->getMessage()
            ], 500);
        }
    }
}
