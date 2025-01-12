<?php

namespace App\Http\Repositories;

use Carbon\Carbon;
use App\Models\Periode;
use Illuminate\Support\Facades\DB;

class PeriodeRepository
{
    protected $periode;

    public function __construct(Periode $periode)
    {
        $this->periode = $periode;
    }

    public function getAll()
    {
        $data = $this->periode
            ->select(DB::raw('
                id,
                tahun,
                bulan,
                CASE bulan 
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
            ->orderBy('tahun', 'asc')
            ->orderBy('bulan', 'asc')
            ->get();

        return $data;
    }

    public function getPaginate($perData)
    {
        $data = $this->periode->paginate($perData);
        return $data;
    }

    public function simpan($data)
    {
        $data = $this->periode->create($data);
        return $data;
    }

    public function getDataById($id)
    {
        $data = $this->periode
            ->select(DB::raw('
                id,
                tahun,
                bulan,
                CASE bulan 
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
            ->where('id', $id)->firstOrFail();
        return $data;
    }

    public function perbarui($id, $data)
    {
        $data = $this->periode->where('id', $id)->update([
            "tahun" => $data['tahun'],
            "bulan" => $data['bulan'],
        ]);
        return $data;
    }

    public function hapus($id)
    {
        $data = [
            $this->periode->where('id', $id)->delete()
        ];
        return $data;
    }
}
