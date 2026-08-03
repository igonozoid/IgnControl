<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Lançada quando alguém tenta criar, editar ou excluir um lançamento
 * financeiro cuja data de vencimento está dentro de um período já
 * fechado (Company::locked_through). É a última linha de defesa —
 * as telas já bloqueiam isso antes, mas isso aqui garante que nenhum
 * outro caminho (artisan tinker, um job futuro, etc.) consiga passar
 * por cima do fechamento sem querer.
 */
class PeriodLockedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este lançamento está em um período fechado e não pode ser criado, editado ou excluído.');
    }
}
