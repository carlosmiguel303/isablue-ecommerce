<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $guarded = ['id'];

    /** Secciones del blog: clave => [nombre, emoji]. */
    const CATEGORIES = [
        'historias' => ['Historias', '💛'],
        'resenas' => ['Reseñas de clientes', '⭐'],
        'recomendados' => ['Juguetes recomendados', '🧸'],
        'novedades' => ['Novedades', '✨'],
    ];

    protected function casts(): array
    {
        return ['published' => 'boolean', 'published_at' => 'datetime'];
    }

    public function scopePublic($query)
    {
        return $query->where('published', true)->where('published_at', '<=', now())->latest('published_at');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category][0] ?? self::CATEGORIES['novedades'][0];
    }

    public function getCategoryIconAttribute(): string
    {
        return self::CATEGORIES[$this->category][1] ?? '';
    }

    /**
     * Dirección para insertar el video en la página (YouTube, Vimeo, TikTok, Facebook o Instagram).
     * Devuelve null si el enlace no es de una plataforma conocida; entonces se muestra como enlace.
     */
    public function getVideoEmbedAttribute(): ?string
    {
        $url = trim((string) $this->video_url);
        if ($url === '') {
            return null;
        }
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }
        if (preg_match('~tiktok\.com/.*/video/(\d+)~', $url, $m)) {
            return 'https://www.tiktok.com/embed/v2/'.$m[1];
        }
        if (preg_match('~instagram\.com/(?:p|reel|reels|tv)/([A-Za-z0-9_-]+)~', $url, $m)) {
            return 'https://www.instagram.com/p/'.$m[1].'/embed';
        }
        if (preg_match('~^https://(?:www\.|m\.)?(?:facebook\.com|fb\.watch)/~', $url)) {
            return 'https://www.facebook.com/plugins/video.php?show_text=false&href='.rawurlencode($url);
        }

        return null;
    }

    /** Los videos verticales (TikTok, Instagram, Shorts) se muestran más angostos. */
    public function getVideoVerticalAttribute(): bool
    {
        return (bool) preg_match('~tiktok\.com|instagram\.com|youtube\.com/shorts/~', (string) $this->video_url);
    }
}
