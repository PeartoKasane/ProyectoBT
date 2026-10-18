<?php

/**
 * RF25: El sistema aplicará filtros para evitar el ingreso de palabras ofensivas en formularios y comentarios.
 *
 * Compara palabra por palabra (no por fragmentos), ignorando mayúsculas y tildes, para no bloquear
 * palabras legítimas que contienen una prohibida dentro, por ejemplo "computadora" ("puta") o "frágil" ("gil").
 */
class FiltroPalabras {
    //Escritas en minúscula y sin tildes, porque el texto se normaliza antes de comparar
    private const PALABRAS_PROHIBIDAS = [
        "boludo", "boluda", "pelotudo", "pelotuda", "imbecil", "idiota", "estupido", "estupida",
        "mierda", "carajo", "puta", "puto", "forro", "forra", "gil", "inutil", "baka", "tarado", "tarada"
    ];

    private const TILDES = [
        "á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u", "ü" => "u", "ñ" => "n"
    ];

    /**
     * @param string $texto Texto ingresado por el usuario.
     * @return bool TRUE si el texto contiene alguna palabra prohibida completa (o su plural).
     */
    public static function contienePalabraOfensiva(string $texto): bool {
        $normalizado = strtr(mb_strtolower($texto, "UTF-8"), self::TILDES);

        //Se separa en palabras: cualquier carácter que no sea letra o número actúa como separador
        $palabras = preg_split("/[^a-z0-9]+/", $normalizado, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($palabras as $palabra) {
            //"idiotas" y "putos" también se consideran: se prueba la palabra y su versión sin la "s" final
            if (in_array($palabra, self::PALABRAS_PROHIBIDAS, true)
                || (str_ends_with($palabra, "s") && in_array(substr($palabra, 0, -1), self::PALABRAS_PROHIBIDAS, true))) {
                return true;
            }
        }

        return false;
    }
}
