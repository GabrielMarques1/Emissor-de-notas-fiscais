<?php

namespace App\Libraries;

class SettingsService
{
    public function get(int $idEmpresa, string $chave): array
    {
        $db = \Config\Database::connect();
        $row = $db->table('empresa_settings')
            ->where('id_empresa', $idEmpresa)
            ->where('chave', $chave)
            ->get(1)->getFirstRow('array');
        if (! $row) return [];
        $json = json_decode((string) ($row['valor'] ?? ''), true);
        return is_array($json) ? $json : [];
    }

    public function save(int $idEmpresa, string $chave, array $valor): bool
    {
        $db = \Config\Database::connect();
        $payload = [
            'id_empresa' => $idEmpresa,
            'chave' => $chave,
            'valor' => json_encode($valor, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        // upsert simples
        $exists = $db->table('empresa_settings')
            ->select('id')->where('id_empresa',$idEmpresa)->where('chave',$chave)
            ->get(1)->getFirstRow('array');
        if ($exists) {
            return (bool) $db->table('empresa_settings')->where('id', (int) $exists['id'])->update($payload);
        }
        $payload['created_at'] = date('Y-m-d H:i:s');
        return (bool) $db->table('empresa_settings')->insert($payload);
    }
}


