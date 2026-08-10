<?php

namespace App\Support;

/**
 * Converte um valor monetário pra texto por extenso em português —
 * peça padrão de qualquer recibo brasileiro ("Cento e vinte e três reais
 * e quarenta e cinco centavos"), que o sistema legado gerava
 * internamente (sem IA/serviço externo, conforme REPORTS_ARCHITECTURE.md)
 * e o Laravel não tem embutido.
 *
 * Cobre até 999 trilhões, mais que suficiente pra qualquer lançamento
 * financeiro real. Moedas suportadas: BRL (padrão), USD, EUR — outras
 * moedas caem no nome genérico "unidades"/"centavos" pra não quebrar.
 */
class ValorPorExtenso
{
    private const UNIDADES = [
        '', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove',
    ];

    private const DEZ_A_DEZENOVE = [
        'dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove',
    ];

    private const DEZENAS = [
        '', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa',
    ];

    private const CENTENAS = [
        '', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos',
        'seiscentos', 'setecentos', 'oitocentos', 'novecentos',
    ];

    /** [singular, plural] por escala de milhar (posição 0 = unidades, sem sufixo). */
    private const ESCALAS = [
        ['', ''],
        ['mil', 'mil'],
        ['milhão', 'milhões'],
        ['bilhão', 'bilhões'],
        ['trilhão', 'trilhões'],
    ];

    private const MOEDAS = [
        'BRL' => ['real', 'reais', 'centavo', 'centavos'],
        'USD' => ['dólar', 'dólares', 'centavo', 'centavos'],
        'EUR' => ['euro', 'euros', 'cêntimo', 'cêntimos'],
    ];

    /**
     * "R$ 1.234,56" -> "mil, duzentos e trinta e quatro reais e
     * cinquenta e seis centavos". Aceita negativo (prefixa "menos").
     */
    public static function porExtenso(float $valor, string $currencyCode = 'BRL'): string
    {
        [$unidadeSing, $unidadePlur, $centavoSing, $centavoPlur] = self::MOEDAS[$currencyCode] ?? ['unidade', 'unidades', 'centavo', 'centavos'];

        $negativo = $valor < 0;
        $valor = abs(round($valor, 2));

        $inteiro = (int) floor($valor);
        $centavos = (int) round(($valor - $inteiro) * 100);

        // Arredondamento pode empurrar 99,995 -> 100,00 sem carregar pro
        // inteiro se não checarmos de novo depois do round.
        if ($centavos === 100) {
            $centavos = 0;
            $inteiro++;
        }

        $partes = [];

        if ($inteiro > 0) {
            $partes[] = self::grupoComEscala($inteiro).' '.($inteiro === 1 ? $unidadeSing : $unidadePlur);
        }

        if ($centavos > 0) {
            $partes[] = self::grupoTres($centavos).' '.($centavos === 1 ? $centavoSing : $centavoPlur);
        }

        if (empty($partes)) {
            return 'zero '.$unidadePlur;
        }

        $texto = implode(' e ', $partes);

        return $negativo ? 'menos '.$texto : $texto;
    }

    /**
     * Quebra o número inteiro em grupos de 3 dígitos (unidades, milhares,
     * milhões...), escreve cada grupo por extenso com sua escala, e junta
     * tudo com vírgula — exceto o último grupo, que usa "e" quando é
     * menor que 100 ou é uma centena redonda (ex.: "mil e cem",
     * "mil e um", mas "mil, cento e um").
     */
    private static function grupoComEscala(int $numero): string
    {
        $grupos = [];
        while ($numero > 0) {
            $grupos[] = $numero % 1000;
            $numero = intdiv($numero, 1000);
        }
        // $grupos[0] = unidades (escala 0), $grupos[1] = milhares (escala 1)...

        $partesTexto = [];
        for ($escala = count($grupos) - 1; $escala >= 0; $escala--) {
            $valorGrupo = $grupos[$escala];
            if ($valorGrupo === 0) {
                continue;
            }

            $texto = self::grupoTres($valorGrupo);

            // "mil" não leva "um" na frente (não é "um mil", e sim "mil").
            if ($escala === 1 && $valorGrupo === 1) {
                $texto = '';
            }

            [$singular, $plural] = self::ESCALAS[$escala];
            $sufixo = $singular === '' ? '' : ' '.($valorGrupo === 1 ? $singular : $plural);

            $partesTexto[] = trim($texto.$sufixo);
        }

        if (count($partesTexto) === 1) {
            return $partesTexto[0];
        }

        $ultimoGrupoValor = $grupos[0];
        $usaE = $ultimoGrupoValor > 0 && ($ultimoGrupoValor < 100 || $ultimoGrupoValor % 100 === 0);

        $ultimo = array_pop($partesTexto);
        $resto = implode(', ', $partesTexto);

        // Se o último grupo (unidades) for zero, ele nem entrou em
        // $partesTexto — nesse caso $ultimo já foi o penúltimo grupo, e
        // não faz sentido "e" artificial.
        if ($ultimoGrupoValor === 0) {
            return $resto === '' ? $ultimo : $resto.', '.$ultimo;
        }

        return $usaE ? $resto.' e '.$ultimo : $resto.', '.$ultimo;
    }

    /** Escreve um número de 0 a 999 por extenso (sem escala/sufixo). */
    private static function grupoTres(int $numero): string
    {
        if ($numero === 100) {
            return 'cem';
        }

        $centena = intdiv($numero, 100);
        $resto = $numero % 100;

        $partes = [];
        if ($centena > 0) {
            $partes[] = self::CENTENAS[$centena];
        }

        if ($resto >= 10 && $resto < 20) {
            $partes[] = self::DEZ_A_DEZENOVE[$resto - 10];
        } elseif ($resto > 0) {
            $dezena = intdiv($resto, 10);
            $unidade = $resto % 10;

            $sub = self::DEZENAS[$dezena];
            if ($unidade > 0) {
                $sub = $sub === '' ? self::UNIDADES[$unidade] : $sub.' e '.self::UNIDADES[$unidade];
            }
            $partes[] = $sub;
        }

        return implode(' e ', array_filter($partes, fn ($p) => $p !== ''));
    }
}
