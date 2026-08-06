<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Lançada quando uma movimentação de saída (ou o lado de saída de uma
 * transferência) pediria mais quantidade do que o saldo disponível.
 */
class InsufficientStockException extends RuntimeException
{
}
