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
    <h2 style="text-align:center;"> EFISIENSI PENGGUNAAN ANGGARAN WALIKOTA</h2>
    <h2 style="text-align:center;"> PERIODE VISI : {{ $data['visi']->period_starts }} s/d {{ $data['visi']->period_ends }}</h2>
    <h23style="text-align:center;">TAHUN {{ $data['tahun'] }} </h3>
        <p>Generated at: {{ $data['generated_at'] }}</p>

        <table class="report">
            <thead>
                <tr>
                    <th scope="col" className="px-4 py-3 border w-[3%]">No.</th>
                    <th scope="col" className="px-4 py-3 border text-center">Tujuan/Sasaran</th>
                    <th scope="col" className="px-4 py-3 border text-center">Indikator</th>
                    <th scope="col" className="px-4 py-3 border text-center">Program</th>
                    <th scope="col" className="px-4 py-3 border text-center">Anggaran</th>
                    <th scope="col" className="px-4 py-3 border text-center">Realisasi</th>
                    <th scope="col" className="px-4 py-3 border text-center">% <br /> Realisasi</th>
                    <th scope="col" className="px-4 py-3 border text-center">Tingkat <br /> Efisiensi</th>
                </tr>
            </thead>

            <tbody>

                @foreach($data['sasaran'] as $key => $item)

                @php
                $rowspan = max(count($item['indikator']), 1);
                @endphp

                <tr>

                    <td rowspan="{{ $rowspan }}" style="text-align:right;">
                        {{ $key + 1 }}
                    </td>

                    <td rowspan="{{ $rowspan }}">
                        {{ $item['sasaran'] }}
                    </td>

                    @if(count($item['indikator']))

                    @php
                    $indikator = $item['indikator'][0];
                    @endphp

                    <td>
                        {{ $indikator['indikator'] }}
                    </td>

                    <td>
                        -
                    </td>

                    <td style="text-align:right">
                        {{ number_format($item['anggaran_perjanjian_kinerja']['murni'],0,',','.') }}
                    </td>

                    <td>
                        -
                    </td>

                    <td>
                        -
                    </td>

                    <td>
                        -
                    </td>

                    @else

                    <td>(belum ada data)</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>

                    @endif

                </tr>

                @if(count($item['indikator']) > 1)

                @foreach($item['indikator'] as $k => $indikator)

                @if($k > 0)

                <tr>

                    <td>
                        {{ $indikator['indikator'] }}
                    </td>

                    <td>-</td>

                    <td style="text-align:right">
                        {{ number_format($item['anggaran_perjanjian_kinerja']['murni'],0,',','.') }}
                    </td>

                    <td>-</td>

                    <td>-</td>

                    <td>-</td>

                </tr>

                @endif

                @endforeach

                @endif

                @endforeach

            </tbody>
        </table>
</body>

</html>