<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeriodeRequest;
use App\Http\Services\PeriodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeriodeController extends Controller
{
    protected $periodeService;

    public function __construct(PeriodeService $periodeService)
    {
        $this->periodeService = $periodeService;
    }

    public function index()
    {
        $judul = "Periode";
        $data = $this->periodeService->getAll();

        $list_month = [
            [
                "name" => "01",
                "value" => "Januari"
            ],
            [
                "name" => "02",
                "value" => "Februari"
            ],
            [
                "name" => "03",
                "value" => "Maret"
            ],
            [
                "name" => "04",
                "value" => "April"
            ],
            [
                "name" => "05",
                "value" => "Mei"
            ],
            [
                "name" => "06",
                "value" => "Juni"
            ],
            [
                "name" => "07",
                "value" => "Juli"
            ],
            [
                "name" => "08",
                "value" => "Agustus"
            ],
            [
                "name" => "09",
                "value" => "September"
            ],
            [
                "name" => "10",
                "value" => "Oktober"
            ],
            [
                "name" => "11",
                "value" => "November"
            ],
            [
                "name" => "12",
                "value" => "Desember"
            ]
        ];
        $list_month = collect($list_month);

        return view('dashboard.periode.index', [
            "judul" => $judul,
            "data" => $data,
            "list_month" => $list_month,
        ]);
    }

    public function simpan(PeriodeRequest $request)
    {
        $check_periode = DB::table('periode')
            ->where([
                'tahun' => $request->tahun,
                'bulan' => $request->bulan
            ])
            ->count();
        
        if ($check_periode == 1) {
            return redirect('dashboard/periode')->with('gagal', 'Periode yang diinputkan sudah ada!');
        }

        $data = $this->periodeService->simpanPostData($request);
        if (!$data[0]) {
            return redirect('dashboard/periode')->with('gagal', $data[1]);
        }
        return redirect('dashboard/periode')->with('berhasil', "Data berhasil disimpan!");
    }

    public function ubah(Request $request)
    {
        $data = $this->periodeService->ubahGetData($request);
        return $data;
    }

    public function perbarui(PeriodeRequest $request)
    {
        $check_periode = DB::table('periode')
            ->where([
                'tahun' => $request->tahun,
                'bulan' => $request->bulan
            ])
            ->where('id', '!=' , $request->id)
            ->count();
        
        if ($check_periode == 1) {
            return redirect('dashboard/periode')->with('gagal', 'Periode yang diinputkan sudah ada!');
        }

        $data = $this->periodeService->perbaruiPostData($request);
        if (!$data[0]) {
            return redirect('dashboard/periode')->with('gagal', $data[1]);
        }
        return redirect('dashboard/periode')->with('berhasil', "Data berhasil diperbarui!");
    }

    public function hapus(Request $request)
    {
        $this->periodeService->hapusPostData($request->id);
        return redirect('dashboard/periode');
    }
}
