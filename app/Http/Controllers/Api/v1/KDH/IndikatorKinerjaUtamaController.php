<?php

namespace App\Http\Controllers\Api\v1\KDH;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Api\v1\MASTER\BaseController;

use App\Models\Sakip\KDH\PohonKinerjaTujuan;
use App\Models\Sakip\KDH\PohonKinerjaSasaran;
use App\Models\Sakip\KDH\PohonKinerjaIndikator;

use App\Models\Sakip\MASTER\MasterSatuan;
use App\Models\MASTER\MasterOpd;

use Illuminate\Support\Facades\Storage;

class IndikatorKinerjaUtamaController extends Controller
{
    // Dikomen Elsa untuk Formula Perhitungan diubah dari text area jadi upload pdf
    // public function update($id, Request $request)
    // {
    //     try {

    //         // cek validasi jika id berformar uuid atau tidak
    //         if(!Str::isUuid($id)){
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Invalid Id, IKU not Found',
    //             ], 422);
    //         }
    //          // cek existing indikator
    //         $indikator = PohonKinerjaIndikator::find($id);
    //         if (!$indikator) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'indikator not found.',
    //             ], 404);
    //         }

    //         $form = $request->validate([                
    //             "baseline" => "required",
    //             // "formula_perhitungan" => "required|image|mimes:jpeg,png,jpg",
    //             // Diubah Elsa karena formula perhitungan upload berupa pdf
    //             "formula_perhitungan" => "nullable|file|mimes:pdf|max:2048",
    //             "sumber_data" => "required|string"
    //         ]);

    //         if ($request->hasFile('formula_perhitungan')) {
    //                 $file = $request->file('formula_perhitungan');
    //                 // 'uploads' is the directory within your MinIO bucket
    //                 $path = Storage::disk('s3')->put('uploads', $file); 
    //         }


    //         $form['formula_perhitungan'] = $request->formula_perhitungan;
    //         $form['sumber_data'] = $request->sumber_data;
    //         $form['defenisi'] = $request->defenisi;
    //         $form['kegunaan'] = $request->kegunaan;
    //         $form['rilis'] = $request->rilis;
    //         $form['updated_by'] = $request->get('payload')->username;

    //         $indikator->update($form);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'IKU updated successfully.',
    //             'data' => $indikator,
    //         ]);
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong!',
    //             'errors' => $th->getMessage()
    //         ], 500);
    //     }
    // }

    public function update($id, Request $request)
    {
        // return $request->all();
        try {

            // Validasi UUID
            if (!Str::isUuid($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Id, IKU not Found',
                ], 422);
            }

            // Cari data indikator
            $indikator = PohonKinerjaIndikator::find($id);

            if (!$indikator) {
                return response()->json([
                    'success' => false,
                    'message' => 'Indikator not found.',
                ], 404);
            }

            // Validasi
            $form = $request->validate([
                'baseline'             => 'required|string',
                'defenisi'             => 'nullable|string',
                'sumber_data'          => 'required|string',
                'rilis'                => 'nullable|string',
                'kegunaan'             => 'nullable|string',

                // PDF maksimal 2 MB
                'formula_perhitungan'  => 'nullable|file|mimes:pdf|max:2048',
            ]);

            // Upload file jika ada file baru
            if ($request->hasFile('formula_perhitungan')) {

                $file = $request->file('formula_perhitungan');

                // Simpan ke MinIO/S3
                $path = Storage::disk('s3')->put('uploads', $file);

                // Simpan path ke database
                $form['formula_perhitungan'] = $path;
            } else {

                // Jika user tidak upload file baru,
                // gunakan file lama
                $form['formula_perhitungan'] = $indikator->formula_perhitungan;
            }

            $form['updated_by'] = $request->get('payload')->username;

            $indikator->update($form);

            return response()->json([
                'success' => true,
                'message' => 'IKU updated successfully.',
                'data'    => $indikator->fresh(),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Throwable $th) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'errors'  => $th->getMessage()
            ], 500);
        }
    }


    public function create(Request $request)
    {
        try {

            if (empty($request->indikator_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong!',
                    'errors' => "Indikator Wajib Diisi"
                ], 500);
            }

            $current_indikator = BaseController::getIndikatorPemda();

            $update = DB::table('pohon_kinerja_indikator')
                ->whereIn('id', $current_indikator)
                ->update(array('is_indikator_kinerja_utama' => false));

            $indikator_id = $request->indikator_id;

            foreach ($indikator_id as $item) {
                $indikator = PohonKinerjaIndikator::find($item);
                if (!$indikator) {
                    return response()->json([
                        'success' => false,
                        'message' => 'indikator not found.',
                    ], 404);
                }

                $update_indikator = DB::table('pohon_kinerja_indikator')
                    ->where('id', $item)
                    ->update(array('is_indikator_kinerja_utama' => true));
            }

            return response()->json([
                'success' => true,
                'message' => 'IKU Created successfully.',
                'data' => $indikator_id,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'errors' => $th->getMessage()
            ], 500);
        }
    }
}
