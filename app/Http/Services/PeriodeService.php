<?php

namespace App\Http\Services;

use App\Http\Repositories\PeriodeRepository;

class PeriodeService
{
    protected $periodeRepository;

    public function __construct(PeriodeRepository $periodeRepository)
    {
        $this->periodeRepository = $periodeRepository;
    }

    public function getAll()
    {
        $data = $this->periodeRepository->getAll();
        return $data;
    }

    public function getDataById($id)
    {
        $data = $this->periodeRepository->getDataById($id);
        return $data;
    }

    public function getPaginate($perData)
    {
        $data = $this->periodeRepository->getPaginate($perData);
        return $data;
    }

    public function simpanPostData($request)
    {
        $validate = $request->validated();
        $data = [true, $this->periodeRepository->simpan($validate)];
        return $data;
    }

    public function ubahGetData($request)
    {
        $data = $this->periodeRepository->getDataById($request->id);
        return $data;
    }

    public function perbaruiPostData($request)
    {
        $validate = $request->validated();
        $data = [true, $this->periodeRepository->perbarui($request->id, $validate)];
        return $data;
    }

    public function hapusPostData($request)
    {
        $data = $this->periodeRepository->hapus($request);
        return $data;
    }
}
