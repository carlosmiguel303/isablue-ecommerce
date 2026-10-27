<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $guarded = [];

    /** Temas de temporada para la barra de anuncios y el color de acento: clave => [nombre, emoji]. */
    const SEASON_THEMES = [
        'normal' => ['Normal (turquesa)', ''],
        'navidad' => ['Navidad', '🎄'],
        'halloween' => ['Halloween', '🎃'],
        'dia-del-nino' => ['Día del Niño', '🎈'],
        'ofertas' => ['Cyber / Ofertas', '🔥'],
        'san-valentin' => ['San Valentín', '💖'],
        'fiestas-patrias' => ['Fiestas Patrias', '🇵🇪'],
        'verano' => ['Verano', '☀️'],
    ];

    /** Frases de la barra superior cuando la tienda aún no guardó las suyas (máximo 5). */
    const DEFAULT_ANNOUNCEMENTS = [
        'Envíos a todo Lima y provincias',
        'Paga fácil y seguro con Yape',
        'Delivery a domicilio en Lima desde S/ 10',
        'Recojo en estaciones del Metropolitano y Tren por S/ 5',
        'Juguetes para cada etapa, de 0 meses a 5 años a más',
    ];

    const MAX_ANNOUNCEMENTS = 5;

    /** Frases guardadas (una por línea) o las de ejemplo si no hay ninguna. */
    public static function announcements(?string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text))));

        return array_slice($lines ?: self::DEFAULT_ANNOUNCEMENTS, 0, self::MAX_ANNOUNCEMENTS);
    }

    public static function valueOf(string $key, string $default = ''): string
    {
        return static::find($key)?->value ?? $default;
    }
}
