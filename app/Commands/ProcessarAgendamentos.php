<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\ReportScheduleModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProcessarAgendamentos extends BaseCommand
{
    protected $group       = 'Reports';
    protected $name        = 'reports:process';
    protected $description = 'Processa agendamentos de relatórios e envia por email';

    public function run(array $params)
    {
        CLI::write('Processando agendamentos de relatórios...', 'yellow');
        
        $model = new ReportScheduleModel();
        $agendamentos = $model
            ->where('is_active', 1)
            ->where('next_run <=', date('Y-m-d H:i:s'))
            ->findAll();

        if (empty($agendamentos)) {
            CLI::write('Nenhum agendamento pendente.', 'green');
            return;
        }

        CLI::write('Encontrados ' . count($agendamentos) . ' agendamento(s) pendente(s).', 'cyan');

        foreach ($agendamentos as $ag) {
            try {
                CLI::write('-----------------------------------', 'white');
                CLI::write('Empresa #' . $ag['id_empresa'] . ' | Agendamento #' . $ag['id_schedule'], 'cyan');
                CLI::write('Tipo: ' . strtoupper($ag['report_type']) . ' | Formato: ' . strtoupper($ag['format']), 'white');
                
                // Gerar relatório
                $arquivo = $this->gerarRelatorio($ag);
                CLI::write('  ✓ Relatório gerado', 'green');
                
                // Enviar por email
                $this->enviarEmail($ag, $arquivo);
                CLI::write('  ✓ Email enviado para: ' . $ag['email_recipients'], 'green');
                
                // Atualizar próximo envio
                $proximoEnvio = $this->calcularProximoEnvio($ag);
                $model->update($ag['id_schedule'], [
                    'last_sent_at' => date('Y-m-d H:i:s'),
                    'next_run' => $proximoEnvio
                ]);
                
                CLI::write('  ✓ Próximo envio: ' . date('d/m/Y H:i', strtotime($proximoEnvio)), 'green');
                CLI::write('✓ SUCESSO!', 'green');
                
            } catch (\Exception $e) {
                CLI::error('✗ Erro ao processar agendamento #' . $ag['id_schedule'] . ': ' . $e->getMessage());
            }
        }

        CLI::write('Processamento finalizado!', 'green');
    }

    private function gerarRelatorio($agendamento)
    {
        $db = \Config\Database::connect();
        
        // Aqui você implementaria a lógica de cada tipo de relatório
        switch ($agendamento['report_type']) {
            case 'vendas':
                return $this->gerarRelatorioVendas($agendamento, $db);
            case 'produtos':
                return $this->gerarRelatorioProdutos($agendamento, $db);
            case 'estoque':
                return $this->gerarRelatorioEstoque($agendamento, $db);
            default:
                throw new \Exception('Tipo de relatório não implementado');
        }
    }

    private function gerarRelatorioVendas($ag, $db)
    {
        // Buscar vendas do período
        $vendas = $db->table('pos_sales')
            ->where('id_empresa', $ag['id_empresa'])
            ->where('status', 'finalized')
            ->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
            ->get()
            ->getResultArray();

        if ($ag['format'] == 'excel') {
            return $this->criarExcel($vendas, 'Relatório de Vendas');
        } else {
            return $this->criarPDF($vendas, 'Relatório de Vendas');
        }
    }

    private function gerarRelatorioProdutos($ag, $db)
    {
        $produtos = $db->table('produtos')
            ->where('id_empresa', $ag['id_empresa'])
            ->get()
            ->getResultArray();

        if ($ag['format'] == 'excel') {
            return $this->criarExcel($produtos, 'Relatório de Produtos');
        } else {
            return $this->criarPDF($produtos, 'Relatório de Produtos');
        }
    }

    private function gerarRelatorioEstoque($ag, $db)
    {
        $alertas = $db->table('stock_alerts')
            ->select('stock_alerts.*, produtos.nome as produto_nome')
            ->join('produtos', 'produtos.id_produto = stock_alerts.id_produto')
            ->where('stock_alerts.id_empresa', $ag['id_empresa'])
            ->where('stock_alerts.status', 'active')
            ->get()
            ->getResultArray();

        if ($ag['format'] == 'excel') {
            return $this->criarExcel($alertas, 'Alertas de Estoque');
        } else {
            return $this->criarPDF($alertas, 'Alertas de Estoque');
        }
    }

    private function criarExcel($dados, $titulo)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($titulo, 0, 31));

        // Cabeçalho
        $sheet->setCellValue('A1', $titulo);
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Dados (simplificado - adapte conforme necessário)
        $row = 3;
        foreach ($dados as $item) {
            $col = 'A';
            foreach ($item as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Salvar
        $filename = WRITEPATH . 'uploads/relatorio_' . time() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);

        return $filename;
    }

    private function criarPDF($dados, $titulo)
    {
        // Implementação simplificada
        $filename = WRITEPATH . 'uploads/relatorio_' . time() . '.pdf';
        // Aqui você implementaria com TCPDF
        return $filename;
    }

    private function enviarEmail($agendamento, $arquivo)
    {
        $email = \Config\Services::email();
        
        $destinatarios = explode(',', $agendamento['email_recipients']);
        
        $email->setFrom('noreply@xfiscal.com', 'xFiscal ERP');
        $email->setTo($destinatarios);
        $email->setSubject('Relatório Agendado - ' . ucfirst($agendamento['report_type']));
        $email->setMessage('Segue anexo o relatório solicitado.');
        $email->attach($arquivo);

        if (!$email->send()) {
            throw new \Exception('Erro ao enviar email: ' . $email->printDebugger(['headers']));
        }

        // Remover arquivo temporário
        @unlink($arquivo);
    }

    private function calcularProximoEnvio($agendamento)
    {
        $time = $agendamento['schedule_time'];
        
        switch ($agendamento['frequency']) {
            case 'daily':
                return date('Y-m-d', strtotime('+1 day')) . ' ' . $time;
                
            case 'weekly':
                return date('Y-m-d', strtotime('+7 days')) . ' ' . $time;
                
            case 'monthly':
                return date('Y-m-d', strtotime('+1 month')) . ' ' . $time;
                
            default:
                return date('Y-m-d H:i:s', strtotime('+1 day'));
        }
    }
}
