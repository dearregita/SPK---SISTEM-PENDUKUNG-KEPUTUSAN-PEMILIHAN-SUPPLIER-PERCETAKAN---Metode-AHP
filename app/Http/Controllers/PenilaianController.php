<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Services\KategoriService;
use App\Http\Services\KriteriaService;
use App\Http\Services\PenilaianService;
use App\Http\Services\SubKriteriaService;
use App\Http\Services\PeriodeService;

class PenilaianController extends Controller
{
    protected $penilaianService, $kriteriaService, $subKriteriaService, $kategoriService, $periodeService;

    public function __construct(PenilaianService $penilaianService, KriteriaService $kriteriaService, SubKriteriaService $subKriteriaService, KategoriService $kategoriService, PeriodeService $periodeService)
    {
        $this->penilaianService = $penilaianService;
        $this->kriteriaService = $kriteriaService;
        $this->subKriteriaService = $subKriteriaService;
        $this->kategoriService = $kategoriService;
        $this->periodeService = $periodeService;
    }

    public function index()
    {
        $judul = 'Penilaian';
        $kriteria = $this->kriteriaService->getAll();

        $matriksNilaiKriteria = DB::table('matriks_nilai_prioritas_utama as mnu')
            ->join('kriteria as k', 'k.id', '=', 'mnu.kriteria_id')
            ->select('mnu.*', 'k.id as kriteria_id', 'k.nama as nama_kriteria')
            ->get();
        $matriksNilaiSubKriteria = DB::table('matriks_nilai_prioritas_kriteria as mnk')
            ->join('kategori as k', 'k.id', '=', 'mnk.kategori_id')
            ->select('mnk.*', 'k.id as kategori_id', 'k.nama as nama_kategori')
            ->get();
        if ($matriksNilaiKriteria->where('kriteria_id', $kriteria->last()->id)->first() == null) {
            return redirect('dashboard/kriteria/perhitungan_utama')->with('gagal', 'Perhitungan Kriteria Utama belum tuntas!');
        } else if ($matriksNilaiSubKriteria->where('kriteria_id', $kriteria->last()->id)->first() == null) {
            return redirect('dashboard/sub_kriteria')->with('gagal', 'Perhitungan Sub Kriteria belum tuntas!');
        }

        $data = $this->penilaianService->getAll();
        $arr_data = $data->toArray();
        $kategori = $this->kategoriService->getAll();

        $current_yearmonth = Carbon::now()->format("Ym");
        $hasil = DB::table('hasil_solusi_ahp as hsa')
            ->join('alternatif as a', 'a.id', '=', 'hsa.alternatif_id')
            ->join('periode as p', 'a.periode_id', 'p.id')
            ->select('hsa.*', 'a.nama as nama_alternatif')
            ->whereRaw('concat(p.tahun,p.bulan) = "' . $current_yearmonth . '"')
            ->get();

        return view('dashboard.penilaian.index', [
            'judul' => $judul,
            'data' => $data,
            'kriteria' => $kriteria,
            'kategori' => $kategori,
            'matriksNilaiKriteria' => $matriksNilaiKriteria,
            'matriksNilaiSubKriteria' => $matriksNilaiSubKriteria,
            'hasil' => $hasil,
            'current_yearmonth' => $arr_data[0]->string_bulan . ' ' . $arr_data[0]->tahun
        ]);
    }

    public function ubah(Request $request)
    {
        $judul = 'Penilaian Alternatif';

        $subKriteria = $this->subKriteriaService->getAll();
        $data = $this->penilaianService->getDataByAlternatifId($request->alternatif_id);

        return view('dashboard.penilaian.ubahPenilaianAlternatif', [
            'judul' => $judul,
            'data' => $data,
            'subKriteria' => $subKriteria,
        ]);
    }

    public function perbarui(Request $request)
    {
        // dd($request->post());

        $this->penilaianService->perbaruiPostData($request);
        $alternatif = $this->penilaianService->getDataByAlternatifId($request->alternatif_id)->alternatif->nama;
        return redirect('dashboard/penilaian')->with('berhasil', ['Data Penilaian Alternatif telah diperbarui!', $alternatif]);
    }

    public function perhitungan_alternatif()
    {
        $penilaian = $this->penilaianService->getAll();
        if ($penilaian->where('sub_kriteria_id', null)->first() != null) {
            return redirect('dashboard/penilaian')->with('gagal', 'Penilaian Alternatif belum tuntas!');
        }

        $matriksNilaiKriteria = DB::table('matriks_nilai_prioritas_utama')->get();
        $matriksNilaiSubKriteria = DB::table('matriks_nilai_prioritas_kriteria')->get();

        // $data = [];
        $alternatif_ids = $penilaian->unique('alternatif_id')->pluck('alternatif_id');
        DB::table('hasil_solusi_ahp')->whereIn('alternatif_id', $alternatif_ids)->delete();

        foreach($penilaian->unique('alternatif_id') as $item) {
            $nilai = 0;
            foreach($penilaian->where('alternatif_id', $item->alternatif_id) as $value) {
                $kriteria = $matriksNilaiKriteria->where('kriteria_id', $value->kriteria_id)->first()->prioritas;
                $subKriteria = $matriksNilaiSubKriteria->where('kriteria_id', $value->kriteria_id)->where('kategori_id', $value->kategori_id)->first();
                $nilai += $kriteria * $subKriteria->prioritas;

                // $data[] = [
                //     'id' => $penilaian->where('alternatif_id', $item->alternatif_id)->where('kriteria_id', $value->kriteria_id)->first()->id,
                //     'kriteria_id' => $value->kriteria_id,
                //     'kriteria' => $kriteria,
                //     'sub_kriteria_id' => $value->sub_kriteria_id,
                //     'sub_kriteria_nama' => $value->subKriteria->nama,
                //     'sub_kriteria' => $subKriteria->prioritas,
                //     'hasil_kali' => $kriteria * $subKriteria->prioritas,
                // ];
            }
            // $data[] = [
            //     'id' => $penilaian->where('alternatif_id', $item->alternatif_id)->first()->alternatif_id,
            //     'nilai' => $nilai,
            // ];

            DB::table('hasil_solusi_ahp')->insert([
                'nilai' => $nilai,
                'alternatif_id' => $item->alternatif_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // dd($data);

        return redirect('dashboard/penilaian')->with('berhasil', ['Perhitungan AHP Alternatif berhasil!', 0]);
    }

    public function hasil_akhir()
    {
        $judul = 'Hasil Akhir';

        $hasil = DB::table('hasil_solusi_ahp as hsa')
            ->join('alternatif as a', 'a.id', '=', 'hsa.alternatif_id')
            ->join('periode as p', 'a.periode_id', 'p.id')
            ->select(DB::raw('
                hsa.*,
                a.nama as nama_alternatif,
                p.tahun,
                CASE p.bulan 
                    WHEN 01 THEN "Januari"
                    WHEN 02 THEN "Februari"
                    WHEN 03 THEN "Maret"
                    WHEN 04 THEN "April"
                    WHEN 05 THEN "Mei"
                    WHEN 06 THEN "Juni"
                    WHEN 07 THEN "Juli"
                    WHEN 08 THEN "Agustus"
                    WHEN 09 THEN "September"
                    WHEN 10 THEN "Oktober"
                    WHEN 11 THEN "November"
                    WHEN 12 THEN "Desember"
                END as string_bulan
            '))
            ->orderByRaw('concat(p.tahun,p.bulan) DESC')
            ->orderBy('hsa.nilai', 'desc')
            ->get();

        $periode = $this->periodeService->getAll();

        return view('dashboard.penilaian.hasil', [
            'judul' => $judul,
            'hasil' => $hasil,
            'periode' => $periode
        ]);
    }

    public function pdf_ahp()
    {
        $judul = 'Laporan Hasil AHP';
        $kriteria = $this->kriteriaService->getAll();

        $matriksNilaiKriteria = DB::table('matriks_nilai_prioritas_utama as mnu')
            ->join('kriteria as k', 'k.id', '=', 'mnu.kriteria_id')
            ->select('mnu.*', 'k.id as kriteria_id', 'k.nama as nama_kriteria')
            ->get();
        $matriksNilaiSubKriteria = DB::table('matriks_nilai_prioritas_kriteria as mnk')
            ->join('kategori as k', 'k.id', '=', 'mnk.kategori_id')
            ->select('mnk.*', 'k.id as kategori_id', 'k.nama as nama_kategori')
            ->get();
        if ($matriksNilaiKriteria->where('kriteria_id', $kriteria->last()->id)->first() == null) {
            return redirect('dashboard/kriteria/perhitungan_utama')->with('gagal', 'Perhitungan Kriteria Utama belum tuntas!');
        } else if ($matriksNilaiSubKriteria->where('kriteria_id', $kriteria->last()->id)->first() == null) {
            return redirect('dashboard/sub_kriteria')->with('gagal', 'Perhitungan Sub Kriteria belum tuntas!');
        }

        $data = $this->penilaianService->getAll();
        $arr_data = $data->toArray();
        $kategori = $this->kategoriService->getAll();

        $current_yearmonth = Carbon::now()->format("Ym");
        $hasil = DB::table('hasil_solusi_ahp as hsa')
            ->join('alternatif as a', 'a.id', '=', 'hsa.alternatif_id')
            ->join('periode as p', 'a.periode_id', 'p.id')
            ->select('hsa.*', 'a.nama as nama_alternatif')
            ->whereRaw('concat(p.tahun,p.bulan) = "' . $current_yearmonth . '"')
            ->get();

        $pdf = PDF::setOptions(['defaultFont' => 'sans-serif'])->loadview('dashboard.pdf.penilaian', [
            'judul' => $judul,
            'data' => $data,
            'kriteria' => $kriteria,
            'kategori' => $kategori,
            'matriksNilaiKriteria' => $matriksNilaiKriteria,
            'matriksNilaiSubKriteria' => $matriksNilaiSubKriteria,
            'hasil' => $hasil,
            'current_yearmonth' => $arr_data[0]->string_bulan . ' ' . $arr_data[0]->tahun,
        ]);

        // return $pdf->download('laporan-penilaian.pdf');
        return $pdf->stream();
    }

    public function pdf_hasil(Request $request)
    {
        $this->validate($request, [
            'periode_id' => 'required'
        ]);

        $periode_id = $request->periode_id;
        $judul = 'Laporan Hasil Akhir';
        $hasil = DB::table('hasil_solusi_ahp as hsa')
            ->join('alternatif as a', 'a.id', '=', 'hsa.alternatif_id')
            ->select('hsa.*', 'a.nama as nama_alternatif')
            ->when($periode_id, function ($query, $periode_id) {
                return $query->where('a.periode_id', $periode_id);
            })
            ->orderBy('hsa.nilai', 'desc')
            ->get();

        $periode = "Semua Periode";
        if (!is_null($periode_id)) {
            $periode_data = $this->periodeService->getDataById($periode_id);
            $periode = $periode_data->string_bulan . ' ' . $periode_data->tahun;
        }

        $pdf = PDF::setOptions(['defaultFont' => 'sans-serif'])->loadview('dashboard.pdf.hasil_akhir', [
            'judul' => $judul,
            'hasil' => $hasil,
            'periode' => $periode,
        ]);

        // return $pdf->download('laporan-penilaian.pdf');
        return $pdf->stream();
    }
}
