<?php

namespace App\Http\Repositories;

use App\Models\Penilaian;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PenilaianRepository
{
    protected $penilaian;

    public function __construct(Penilaian $penilaian)
    {
        $this->penilaian = $penilaian;
    }

    public function getAll()
    {
        $current_yearmonth = Carbon::now()->format("Ym");
        $data = DB::table('penilaian as a')
            ->join('alternatif as b', 'a.alternatif_id', 'b.id')
            ->join('periode as c', 'b.periode_id', 'c.id')
            ->leftJoin('sub_kriteria as d', 'a.sub_kriteria_id', 'd.id')
            ->leftJoin('kategori as e', 'd.kategori_id', 'e.id')
            ->select(DB::raw('
                a.id,
                c.tahun,
                CASE c.bulan 
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
                END as string_bulan,
                a.alternatif_id,
                a.kriteria_id,
                a.sub_kriteria_id,
                b.nama as alternatif_nama,
                e.nama as kategori_nama,
                e.id as kategori_id
            '))
            ->whereRaw('concat(c.tahun,c.bulan) = "' . $current_yearmonth . '"')
            ->orderBy('a.created_at', 'ASC')
            ->get();

        return $data;
    }

    public function getPaginate($perData)
    {
        $data = $this->penilaian->paginate($perData);
        return $data;
    }

    public function simpan($data)
    {
        $data = $this->penilaian->create($data);
        return $data;
    }

    public function getDataById($id)
    {
        $data = $this->penilaian->where('id', $id)->firstOrFail();
        return $data;
    }

    public function getDataByAlternatifId($id)
    {
        $data = $this->penilaian->where('alternatif_id', $id)->firstOrFail();
        return $data;
    }

    public function perbarui($request)
    {
        foreach ($this->penilaian->where('alternatif_id', $request->alternatif_id)->get() as $item) {
            $this->penilaian->where('alternatif_id', $request->alternatif_id)->where('kriteria_id', $item->kriteria_id)->update([
                'sub_kriteria_id' => $request[$item->kriteria_id],
            ]);
        }
        return true;
    }
}
