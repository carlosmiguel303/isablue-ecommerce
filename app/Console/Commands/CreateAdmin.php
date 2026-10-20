<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'isablue:admin';

    protected $description = 'Crea un administrador sin publicar ni pasar su contraseña como argumento.';

    public function handle(): int
    {
        $name = $this->ask('Nombre del administrador');
        $email = $this->ask('Correo del administrador');
        $password = $this->secret('Contraseña nueva (mínimo 12 caracteres)');
        $confirmation = $this->secret('Repite la contraseña');
        $validator = Validator::make([
            'name' => $name, 'email' => $email,
            'password' => $password, 'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) { $this->error($error); }
            return self::FAILURE;
        }
        $user = new User;
        $user->forceFill([
            'name' => $name, 'email' => $email,
            'password' => Hash::make($password), 'role' => 'admin',
        ])->save();
        $this->info('Administrador creado. Ingresa desde /ingresar.');
        return self::SUCCESS;
    }
}
