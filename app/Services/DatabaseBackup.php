<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseBackup
{
    public static function folder(): string
    {
        return storage_path('app/private/backups');
    }

    public static function create(): string
    {
        if (config('database.default') !== 'mysql') {
            throw new \RuntimeException('Se necesita MySQL para el respaldo.');
        }
        $sql = DB::transaction(function () {
            $text = "SET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n";
            foreach (DB::select('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'') as $row) {
                $table = array_values((array) $row)[0];
                $quoted = chr(96).str_replace(chr(96), chr(96).chr(96), $table).chr(96);
                $schema = array_values((array) DB::selectOne('SHOW CREATE TABLE '.$quoted))[1];
                $text .= 'DROP TABLE IF EXISTS '.$quoted.";\n".$schema.";\n";
                foreach (DB::table($table)->cursor() as $record) {
                    $values = [];
                    foreach ((array) $record as $v) {
                        $values[] = $v === null ? 'NULL' : DB::getPdo()->quote((string) $v);
                    }$text .= 'INSERT INTO '.$quoted.' VALUES ('.implode(',', $values).");\n";
                }
            }

            return $text."SET FOREIGN_KEY_CHECKS=1;\n";
        });
        File::ensureDirectoryExists(self::folder(), 0700);
        $name = 'isablue-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sql.gz.enc';
        $encrypted = Crypt::encryptString(base64_encode(gzencode($sql, 6)));
        File::put(self::folder().'/'.$name.'.tmp', $encrypted);
        rename(self::folder().'/'.$name.'.tmp', self::folder().'/'.$name);
        @chmod(self::folder().'/'.$name, 0600);
        if (gzdecode(base64_decode(Crypt::decryptString(File::get(self::folder().'/'.$name)))) !== $sql) {
            throw new \RuntimeException('La verificación del respaldo falló.');
        }
        File::put(self::folder().'/last.json', json_encode(['file' => $name, 'created_at' => now()->toIso8601String(), 'sha256' => hash('sha256', $encrypted), 'verified' => true]));

        return $name;
    }

    public static function latest(): array
    {
        return is_file(self::folder().'/last.json') ? (json_decode(file_get_contents(self::folder().'/last.json'), true) ?: []) : [];
    }

    public static function due(): bool
    {
        $last = self::latest();

        return empty($last['created_at']) || Carbon::parse($last['created_at'])->lt(now()->subDay());
    }

    public static function download()
    {
        $last = self::latest();
        $file = $last['file'] ?? '';
        abort_unless(preg_match('/^isablue-[0-9]{8}-[0-9]{6}-[a-f0-9]{6}\.sql\.gz\.enc$/', $file), 404);
        $data = base64_decode(Crypt::decryptString(file_get_contents(self::folder().'/'.$file)));

        return response($data)->header('Content-Type', 'application/gzip')->header('Content-Disposition', 'attachment; filename="'.substr($file, 0, -4).'"')->header('Cache-Control', 'private, no-store');
    }
}
