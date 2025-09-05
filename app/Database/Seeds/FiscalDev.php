<?php

namespace App\Database\Seeds;

class FiscalDev extends \CodeIgniter\Database\Seeder
{
    public function run()
    {
        $codigos_uf = ['17'];
        $nomes_ufs = ['Tocantins'];
        $ufs = ['TO'];

        $i = 0;
        foreach($codigos_uf as $codigo) :
            $this->db->table('ufs')->insert([
                'id_uf'     => $codigo,
                'codigo_uf' => $codigo,
                'estado'    => $nomes_ufs[$i],
                'uf'        => $ufs[$i]
            ]);

            $i++;
        endforeach;


        // ------------------------------------------------------------------------------------------------------------------------ //

        $codigos_municipios = [
            '1721000',
        ];

        $municipios = [
            'Palmas',
        ];

        $ids = [
            '17',
        ];

        $i = 0;
        foreach($ids as $id) :
            $this->db->table('municipios')->insert([
                'codigo'    => $codigos_municipios[$i],
                'municipio' => strtoupper($municipios[$i]),
                'id_uf'     => $id
            ]);

            $i++;
        endforeach;
    }
}
