<?php

declare(strict_types=1);

class PasswordGenerator
{
    /**
     * Genera una contraseña aleatoria segun las siguientes reglas:
     * - Mínimo 8 caracteres y máximo 32
     * - Incluye al menos: una letra minúscula, una mayúscula, un dígito y un símbolo
     */
    public static function generate(int $length = 8): string
    {
        if ($length < 8) $length = 8;
        if ($length > 32) $length = 32;

        // caracteres a usar
        $lower = "abcdefghjkmnpqrstuvwxyz";
        $upper = "ABCDEFGHJKMNPQRSTUVWXYZ";
        $digits = "23456789";
        $symbols = "@#$%";

        // asegurar al menos un caracter de cada tipo
        $password = [];
        $password[] = $lower[random_int(0, strlen($lower) - 1)];
        $password[] = $upper[random_int(0, strlen($upper) - 1)];
        $password[] = $digits[random_int(0, strlen($digits) - 1)];
        $password[] = $symbols[random_int(0, strlen($symbols) - 1)];

        $all = $lower . $upper . $digits . $symbols;

        while (count($password) < $length) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        // mezclar aleatoriamente
        for ($i = count($password) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$password[$i], $password[$j]] = [$password[$j], $password[$i]];
        }

        return implode("", $password);
    }
}
