<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title> Renstra</title>
    <style>
        /* Page */
        @page {
            margin: 8mm 6mm;
            /* sangat kecil untuk maksimal area */
        }

        html,
        body {
            font-family: "DejaVu Sans", "Helvetica", Arial, sans-serif;
            /* DejaVu mendukung unicode */
            font-size: 9px;
            /* baseline kecil; bisa 8px jika perlu */
            color: #111;
        }

        /* Overall table */
        table.report {
            width: 100%;
            border-collapse: collapse;
            /* table-layout: fixed; kunci supaya kolom mengikuti lebar yang ditentukan */
            word-wrap: break-word;
            /* agar teks panjang membungkus */
            font-size: 9px;
        }

        /* Header styling */
        thead th {
            background: #f5f5f5;
            font-weight: 700;
            padding: 4px 6px;
            border: 1px solid #bbb;
            font-size: 8.5px;
        }

        /* Cells */
        td {
            border: 1px solid #ccc;
            padding: 4px 6px;
            /* kecilkan padding */
            vertical-align: top;
            font-size: 8.5px;
            line-height: 1.1;
        }

        .indent {
            display: inline-block;
        }

        /* hindari pemotongan baris pada dompdf (sebagian didukung) */
        tr {
            page-break-inside: avoid;
        }

        /* sesuaikan ukuran kolom */
        .col-no {
            width: 15px;
        }

        .col-name {
            width: 300px;
        }

        .col-code {
            width: 120px;
        }
    </style>
</head>

<body>
    <h2 style="text-align:center;"> DATA KINERJA</h2>
    <h2 style="text-align:center;"> {{ $data['opd']['nama_opd'] }}</h2>
    <h2 style="text-align:center;"> PERIODE VISI : {{ $data['visi']->period_starts }} s/d {{ $data['visi']->period_ends }}</h2>
    <p>Generated at: {{ $data['generated_at'] }}</p>

    <table class="report">
        <thead>
            <!-- header baris 2 -->
            <tr>
                <th scope="col" className="px-4 py-3 border w-[3%]" rowSpan="2">No.</th>
                <th scope="col" className="px-4 py-3 border text-center" rowSpan="2">Tujuan/Sasaran</th>
                <th scope="col" className="px-4 py-3 border text-center" rowSpan="2">Indikator</th>
                <th scope="col" className="px-4 py-3 border text-center" colSpan="3">Tahun n -1</th>
                <th scope="col" className="px-4 py-3 border text-center" colSpan="3">Tahun 1</th>
                <th scope="col" className="px-4 py-3 border text-center" colSpan="3">Tahun 2</th>
                <th scope="col" className="px-4 py-3 border text-center" colSpan="3">Tahun 3</th>
                <th scope="col" className="px-4 py-3 border text-center" colSpan="3">Tahun 4</th>
                <th scope="col" className="px-4 py-3 border text-center" colSpan="3">Tahun 5</th>
            </tr>
            <tr>
                <th scope="col" className="px-4 py-3 border text-center">T</th>
                <th scope="col" className="px-4 py-3 border text-center">R</th>
                <th scope="col" className="px-4 py-3 border text-center">C</th>
                <th scope="col" className="px-4 py-3 border text-center">T</th>
                <th scope="col" className="px-4 py-3 border text-center">R</th>
                <th scope="col" className="px-4 py-3 border text-center">C</th>
                <th scope="col" className="px-4 py-3 border text-center">T</th>
                <th scope="col" className="px-4 py-3 border text-center">R</th>
                <th scope="col" className="px-4 py-3 border text-center">C</th>
                <th scope="col" className="px-4 py-3 border text-center">T</th>
                <th scope="col" className="px-4 py-3 border text-center">R</th>
                <th scope="col" className="px-4 py-3 border text-center">C</th>
                <th scope="col" className="px-4 py-3 border text-center">T</th>
                <th scope="col" className="px-4 py-3 border text-center">R</th>
                <th scope="col" className="px-4 py-3 border text-center">C</th>
                <th scope="col" className="px-4 py-3 border text-center">T</th>
                <th scope="col" className="px-4 py-3 border text-center">R</th>
                <th scope="col" className="px-4 py-3 border text-center">C</th>
            </tr>
        </thead>

        <tbody>
            @if($data['tujuan']->count() > 0)

            @foreach($data['tujuan'] as $keyTujuan => $tujuan)

            {{-- TUJUAN --}}
            <tr>
                <td colspan="21" style="font-weight:bold;background:#f3f4f6;">
                    Tujuan {{ $keyTujuan+1 }} :
                    {{ strtoupper($tujuan['tujuan']) }}
                </td>
            </tr>

            {{-- ================= INDIKATOR TUJUAN ================= --}}
            @if(isset($tujuan['indikator_tujuan']) && $tujuan['indikator_tujuan']->count())

            @foreach($tujuan['indikator_tujuan'] as $indikator)

            <tr style="background:#fafafa;">
                <td colspan="3">
                    <strong>Indikator :</strong>
                    {{ $indikator['indikator'] }}
                </td>

                {{-- Tahun n-1 --}}
                <td>{{ $indikator['target_1'] }}</td>
                <td>{{ $indikator['realisasi_1'] }}</td>
                <td>{{ $indikator['capaian_1'] }}</td>

                {{-- Tahun 1 --}}
                <td>{{ $indikator['target_2'] }}</td>
                <td>{{ $indikator['realisasi_2'] }}</td>
                <td>{{ $indikator['capaian_2'] }}</td>

                {{-- Tahun 2 --}}
                <td>{{ $indikator['target_3'] }}</td>
                <td>{{ $indikator['realisasi_3'] }}</td>
                <td>{{ $indikator['capaian_3'] }}</td>

                {{-- Tahun 3 --}}
                <td>{{ $indikator['target_4'] }}</td>
                <td>{{ $indikator['realisasi_4'] }}</td>
                <td>{{ $indikator['capaian_4'] }}</td>

                {{-- Tahun 4 --}}
                <td>{{ $indikator['target_5'] }}</td>
                <td>{{ $indikator['realisasi_5'] }}</td>
                <td>{{ $indikator['capaian_5'] }}</td>

                {{-- Tahun 5 --}}
                <td>{{ $indikator['target_6'] }}</td>
                <td>{{ $indikator['realisasi_6'] }}</td>
                <td>{{ $indikator['capaian_6'] }}</td>

            </tr>

            @endforeach

            @endif

            {{-- ================= SASARAN ================= --}}
            @if($tujuan['sasaran']->count())

            @foreach($tujuan['sasaran'] as $keySasaran => $sasaran)

            @php
            $rowspan = max($sasaran['indikator_sasaran']->count(),1);
            @endphp

            <tr>

                <td rowspan="{{ $rowspan }}">
                    {{ chr(65+$keySasaran) }}
                </td>

                <td rowspan="{{ $rowspan }}">
                    {{ $sasaran['sasaran'] }}
                </td>

                @if($sasaran['indikator_sasaran']->count())

                @php
                $indikator = $sasaran['indikator_sasaran'][0];
                @endphp

                <td>1. {{ $indikator['indikator'] }}</td>

                {{-- Tahun n-1 --}}
                <td>{{ $indikator['target_1'] }}</td>
                <td>{{ $indikator['realisasi_1'] }}</td>
                <td>{{ $indikator['capaian_1'] }}</td>

                {{-- Tahun 1 --}}
                <td>{{ $indikator['target_2'] }}</td>
                <td>{{ $indikator['realisasi_2'] }}</td>
                <td>{{ $indikator['capaian_2'] }}</td>

                {{-- Tahun 2 --}}
                <td>{{ $indikator['target_3'] }}</td>
                <td>{{ $indikator['realisasi_3'] }}</td>
                <td>{{ $indikator['capaian_3'] }}</td>

                {{-- Tahun 3 --}}
                <td>{{ $indikator['target_4'] }}</td>
                <td>{{ $indikator['realisasi_4'] }}</td>
                <td>{{ $indikator['capaian_4'] }}</td>

                {{-- Tahun 4 --}}
                <td>{{ $indikator['target_5'] }}</td>
                <td>{{ $indikator['realisasi_5'] }}</td>
                <td>{{ $indikator['capaian_5'] }}</td>

                {{-- Tahun 5 --}}
                <td>{{ $indikator['target_6'] }}</td>
                <td>{{ $indikator['realisasi_6'] }}</td>
                <td>{{ $indikator['capaian_6'] }}</td>

                @else

                <td colspan="19" align="center">
                    ( No Data )
                </td>

                @endif

            </tr>

            {{-- Indikator sasaran berikutnya --}}
            @if($sasaran['indikator_sasaran']->count() > 1)

            @foreach($sasaran['indikator_sasaran'] as $k => $indikator)

            @if($k>0)

            <tr>

                <td>{{ $k+1 }}. {{ $indikator['indikator'] }}</td>

                <td>{{ $indikator['target_1'] }}</td>
                <td>{{ $indikator['realisasi_1'] }}</td>
                <td>{{ $indikator['capaian_1'] }}</td>

                <td>{{ $indikator['target_2'] }}</td>
                <td>{{ $indikator['realisasi_2'] }}</td>
                <td>{{ $indikator['capaian_2'] }}</td>

                <td>{{ $indikator['target_3'] }}</td>
                <td>{{ $indikator['realisasi_3'] }}</td>
                <td>{{ $indikator['capaian_3'] }}</td>

                <td>{{ $indikator['target_4'] }}</td>
                <td>{{ $indikator['realisasi_4'] }}</td>
                <td>{{ $indikator['capaian_4'] }}</td>

                <td>{{ $indikator['target_5'] }}</td>
                <td>{{ $indikator['realisasi_5'] }}</td>
                <td>{{ $indikator['capaian_5'] }}</td>

                <td>{{ $indikator['target_6'] }}</td>
                <td>{{ $indikator['realisasi_6'] }}</td>
                <td>{{ $indikator['capaian_6'] }}</td>

            </tr>

            @endif

            @endforeach

            @endif

            @endforeach

            @endif

            @endforeach

            @else

            <tr>
                <td colspan="21" align="center">
                    Tidak ada data
                </td>
            </tr>

            @endif
        </tbody>
    </table>
</body>

</html>