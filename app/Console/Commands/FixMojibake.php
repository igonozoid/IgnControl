<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

/**
 * Corrige mojibake (UTF-8 mal codificado / double-encoding) introduzido
 * pela importação do dump do servidor sem --default-character-set=utf8mb4.
 *
 * A primeira versão deste comando fazia um CONVERT(... USING latin1 ...)
 * cego em massa via SQL, assumindo que TODA a base tinha o mesmo nível
 * de corrupção (2 camadas). Isso quebrou: nem toda linha/coluna tem a
 * mesma profundidade de mojibake (algumas vieram com 1 camada, outras
 * com 2, outras já corretas), e um CONVERT às cegas pode gerar bytes
 * UTF-8 inválidos (erro 1300).
 *
 * Esta versão processa linha a linha em PHP, e por string:
 *   1. só mexe se detectar sinal de mojibake ("Ã" ou "Â" no texto —
 *      assinatura clássica de UTF-8 lido como Latin-1);
 *   2. tenta reverter uma camada (reinterpretar como Latin-1);
 *   3. só aceita o resultado se ele for UTF-8 válido;
 *   4. repete até não sobrar mais sinal de mojibake ou até 2 camadas
 *      (o máximo observado nos dados reais), o que vier primeiro;
 *   5. se em algum passo o resultado ficar inválido, mantém a última
 *      versão válida conhecida (nunca grava lixo).
 *
 * Uso: php artisan mojibake:fix
 *      php artisan mojibake:fix --dry-run   (só mostra amostras, não grava)
 */
class FixMojibake extends Command
{
    protected $signature = 'mojibake:fix {--dry-run : Só mostra amostras do que mudaria, sem gravar}';

    protected $description = 'Corrige texto com encoding duplicado (mojibake) causado pela importação sem charset explícito';

    private function looksLikeMojibake(string $value): bool
    {
        return str_contains($value, 'Ã') || str_contains($value, 'Â');
    }

    private function reverseOneLayer(string $value): ?string
    {
        $candidate = mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');

        if ($candidate === false || ! mb_check_encoding($candidate, 'UTF-8')) {
            return null;
        }

        return $candidate;
    }

    private function fix(string $value): string
    {
        $current = $value;

        for ($layer = 0; $layer < 2; $layer++) {
            if (! $this->looksLikeMojibake($current)) {
                break;
            }

            $reversed = $this->reverseOneLayer($current);

            if ($reversed === null) {
                break;
            }

            $current = $reversed;
        }

        return $current;
    }

    public function handle(): int
    {
        $database = DB::getDatabaseName();
        $dryRun = (bool) $this->option('dry-run');

        $columns = DB::select(
            "SELECT TABLE_NAME, COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND DATA_TYPE IN ('char', 'varchar', 'text', 'tinytext', 'mediumtext', 'longtext')
             ORDER BY TABLE_NAME, COLUMN_NAME",
            [$database]
        );

        if (empty($columns)) {
            $this->error('Nenhuma coluna de texto encontrada — confere se o banco/conexão está certo.');

            return self::FAILURE;
        }

        $this->info(sprintf('%d colunas de texto encontradas em %s.', count($columns), $database));

        $totalRowsChanged = 0;
        $samplesShown = 0;

        foreach ($columns as $col) {
            $table = $col->TABLE_NAME;
            $column = $col->COLUMN_NAME;

            $primaryKey = DB::selectOne(
                "SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'
                 LIMIT 1",
                [$database, $table]
            );

            if (! $primaryKey) {
                continue; // tabela sem PK simples — pula, não é o caso das tabelas de negócio aqui
            }

            $pkColumn = $primaryKey->COLUMN_NAME;

            $rows = DB::table($table)
                ->select([$pkColumn, $column])
                ->whereNotNull($column)
                ->where($column, '<>', '')
                ->get();

            $updates = [];

            foreach ($rows as $row) {
                $original = $row->{$column};

                if (! $this->looksLikeMojibake($original)) {
                    continue;
                }

                $fixed = $this->fix($original);

                if ($fixed !== $original) {
                    $updates[] = ['pk' => $row->{$pkColumn}, 'from' => $original, 'to' => $fixed];
                }
            }

            if (empty($updates)) {
                continue;
            }

            if ($samplesShown < 8) {
                foreach (array_slice($updates, 0, 2) as $u) {
                    $this->line("{$table}.{$column}: \"{$u['from']}\" -> \"{$u['to']}\"");
                    $samplesShown++;
                }
            }

            if (! $dryRun) {
                foreach ($updates as $u) {
                    DB::table($table)->where($pkColumn, $u['pk'])->update([$column => $u['to']]);
                }
            }

            $totalRowsChanged += count($updates);
        }

        $this->newLine();

        if ($dryRun) {
            $this->warn("--dry-run: {$totalRowsChanged} valores seriam corrigidos. Nada foi gravado. Rode sem essa flag pra aplicar.");
        } else {
            $this->info("Concluído: {$totalRowsChanged} valores corrigidos. Confere no navegador.");
        }

        return self::SUCCESS;
    }
}
