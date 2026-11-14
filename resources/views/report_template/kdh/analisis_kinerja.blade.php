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
    <h2 style="text-align:center;"> ANALISIS KINERJA WALIKOTA</h2>
    <h2 style="text-align:center;"> BERISI UPAYA DAN HAMBATAN DAN UPAYA DI MASA YANG AKAN DATANG</h2>
    <h2 style="text-align:center;"> PERIODE VISI : {{ $data['visi']->period_starts }} s/d {{ $data['visi']->period_ends }}</h2>
    <h23style="text-align:center;">TAHUN {{ $data['tahun'] }} </h3>
        <p>Generated at: {{ $data['generated_at'] }}</p>

        <table class="report">
            <thead>
                <tr>
                    <th rowspan="2">No.</th>
                    <th rowspan="2">Tujuan / Sasaran</th>
                    <th rowspan="2">Indikator</th>
                    <th colspan="2">Aksi yang dilakukan</th>
                    <th rowspan="2">Hambatan yang dihadapi</th>
                    <th rowspan="2">Upaya di masa yang akan datang</th>
                </tr>
                <tr>
                    <th>Aksi</th>
                    <th>Realisasi</th>
                </tr>
            </thead>

            <tbody>

                @foreach($data['sasaran'] as $key => $item)

                @php
                $rowspan = max(count($item['indikator']), 1);
                @endphp

                <tr>

                    <td rowspan="{{ $rowspan }}">
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

                        @if($indikator['langkah']->count())

                        @foreach($indikator['langkah'] as $langkah)
                        • {{ $langkah->langkah }}<br>
                        @endforeach

                        @else
                        (belum ada data)
                        @endif

                    </td>

                    <td>

                        @if($indikator['rencana_aksi'])

                        TW I : {{ $indikator['rencana_aksi']->realisasi_tw1 ?? '-' }}<br>
                        TW II : {{ $indikator['rencana_aksi']->realisasi_tw2 ?? '-' }}<br>
                        TW III : {{ $indikator['rencana_aksi']->realisasi_tw3 ?? '-' }}<br>
                        TW IV : {{ $indikator['rencana_aksi']->realisasi_tw4 ?? '-' }}

                        @else

                        (belum ada data)

                        @endif

                    </td>

                    <td>
                        {{ $indikator['rencana_aksi']->hambatan ?? '(No Data)' }}
                    </td>

                    <td>
                        {{ $indikator['rencana_aksi']->tindak_lanjut ?? '(No Data)' }}
                    </td>

                    @else

                    <td colspan="5">(No Data)</td>

                    @endif

                </tr>

                @if(count($item['indikator']) > 1)

                @foreach($item['indikator'] as $k => $indikator)

                @if($k > 0)

                <tr>

                    <td>
                        {{ $indikator['indikator'] }}
                    </td>

                    <td>

                        @if($indikator['langkah']->count())

                        @foreach($indikator['langkah'] as $langkah)
                        • {{ $langkah->langkah }}<br>
                        @endforeach

                        @else
                        (belum ada data)
                        @endif

                    </td>

                    <td>

                        @if($indikator['rencana_aksi'])

                        TW I : {{ $indikator['rencana_aksi']->realisasi_tw1 ?? '-' }}<br>
                        TW II : {{ $indikator['rencana_aksi']->realisasi_tw2 ?? '-' }}<br>
                        TW III : {{ $indikator['rencana_aksi']->realisasi_tw3 ?? '-' }}<br>
                        TW IV : {{ $indikator['rencana_aksi']->realisasi_tw4 ?? '-' }}

                        @else

                        (belum ada data)

                        @endif

                    </td>

                    <td>
                        {{ $indikator['rencana_aksi']->hambatan ?? '(No Data)' }}
                    </td>

                    <td>
                        {{ $indikator['rencana_aksi']->tindak_lanjut ?? '(No Data)' }}
                    </td>

                </tr>

                @endif

                @endforeach

                @endif

                @endforeach

            </tbody>
        </table>
</body>

</html>