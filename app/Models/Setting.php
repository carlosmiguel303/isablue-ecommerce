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

    public static function valueOf(string $key, string $default = ''): string
    {
        return static::find($key)?->value ?? $default;
    }
}
