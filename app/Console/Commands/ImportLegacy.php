<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportLegacy extends Command
{
    protected $signature = 'isablue:import {file}';

    protected $description = 'Importación única y transaccional del snapshot PostgreSQL; conserva todos los campos en legacy_records.';

    public function handle(): int
    {
        if (DB::table('legacy_records')->exists() || Product::exists() || User::exists()) {
            $this->error('La base de destino debe estar vacía. La importación no sobrescribe datos.');

            return 1;
        }
        $data = json_decode(file_get_contents($this->argument('file')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($data) {
            foreach ($data as $table => $rows) {
                foreach ($rows as $row) {
                    DB::table('legacy_records')->insert(['source_table' => $table, 'source_id' => $row['id'], 'payload' => json_encode($row, JSON_UNESCAPED_UNICODE)]);
                }
            }
            foreach ($data['users'] as $u) {
                $user = new User;
                $user->forceFill(['id' => $u['id'], 'name' => trim($u['first_name'].' '.$u['last_name']), 'email' => $u['email'], 'password' => $u['password'], 'role' => $u['user_type'] === 'ADMIN' ? 'admin' : 'customer', 'phone' => $u['cellphone'], 'address' => $u['address'], 'legacy_data' => $u, 'created_at' => $u['date_created'], 'updated_at' => $u['date_updated']])->save();
            }
            foreach ($data['categories'] as $c) {
                $cat = new Category;
                $cat->forceFill(['id' => $c['id'], 'name' => $c['name'], 'slug' => Str::slug($c['name']).'-origen-'.$c['id'], 'active' => false, 'created_at' => $c['date_created'], 'updated_at' => $c['date_updated']])->save();
            }
            foreach ($data['products'] as $p) {
                $product = new Product;
                $product->forceFill(['id' => $p['id'], 'name' => $p['name'], 'slug' => Str::slug($p['name']).'-origen-'.$p['id'], 'sku' => $p['code'], 'description' => $p['description'] ?? '', 'price' => $p['price'], 'stock' => $p['stock'], 'active' => $p['active'] ?? true, 'catalog_visible' => false, 'source' => 'postgresql', 'legacy_data' => $p, 'created_at' => $p['date_created'], 'updated_at' => $p['date_updated']])->save();
                if ($p['category_entity_id']) {
                    $product->categories()->sync([$p['category_entity_id']]);
                }$image = basename(parse_url($p['url_image'] ?? '', PHP_URL_PATH) ?? '');
                if ($image && is_file(public_path('assets/images/'.$image))) {
                    $product->images()->create(['path' => '/assets/images/'.$image]);
                }if ($p['stock'] !== null) {
                    StockMovement::create(['product_id' => $product->id, 'before' => null, 'change' => $p['stock'], 'after' => $p['stock'], 'reason' => 'Saldo importado desde PostgreSQL']);
                }
            }
            foreach ($data['orders'] as $o) {
                $u = User::find($o['user_entity_id']);
                $order = new Order;
                $items = array_filter($data['order_products'], fn ($i) => $i['order_entity_id'] === $o['id']);
                $sum = 0;
                foreach ($items as $i) {
                    $sum += round($i['price'] * $i['quantity'], 2);
                }$order->forceFill(['id' => $o['id'], 'reference' => 'ORIGEN-'.$o['id'], 'public_token' => Str::uuid(), 'user_id' => $u?->id, 'customer_name' => $u?->name, 'customer_email' => $u?->email, 'phone' => $u?->phone, 'address' => $u?->address, 'subtotal' => $sum, 'total' => $sum, 'status' => $o['order_state'] === 'CONFIRMED' ? 'confirmed' : 'cancelled', 'payment_status' => 'unverified', 'source' => 'postgresql', 'currency' => 'UNKNOWN', 'legacy_data' => $o, 'created_at' => $o['date_created'], 'updated_at' => $o['date_created']])->save();
                foreach ($items as $i) {
                    $p = Product::find($i['product_id']);
                    $order->items()->create(['id' => $i['id'], 'product_id' => $p?->id, 'name' => $p?->name ?? 'Producto histórico '.$i['product_id'], 'sku' => $p?->sku, 'quantity' => $i['quantity'], 'unit_price' => $i['price'], 'total' => round($i['price'] * $i['quantity'], 2), 'legacy_data' => $i]);
                }
            }
            $names = ['Rompecabezas', 'Estimulación temprana', 'Juguetes sensoriales', 'Bloques y construcción', 'Juguetes educativos', 'Encaje y clasificación', 'Motricidad fina', 'Música y sonidos', 'Juguetes de madera', 'Peluches', 'Juegos de aprendizaje', 'Juegos de imaginación'];
            $cats = [];
            foreach ($names as $n => $name) {
                $cats[$name] = Category::create(['name' => $name, 'slug' => Str::slug($name), 'sort_order' => $n, 'image' => '/assets/images/'.(['toy-puzzle.svg', 'toy-teddy.svg', 'toy-teddy.svg', 'toy-blocks.svg', 'toy-montessori.svg', 'toy-montessori.svg'][$n % 6])])->id;
            }
            $six = [
                ['Set Montessori de Madera', 'ISA-MONT-001', 'Juguete didáctico para coordinación, colores y motricidad fina.', 89, 'toy-montessori.svg', ['Juguetes educativos', 'Juguetes de madera', 'Encaje y clasificación']],
                ['Rompecabezas Infantil Animales', 'ISA-PUZ-002', 'Puzzle educativo para estimular memoria, concentración y paciencia.', 45, 'toy-puzzle.svg', ['Rompecabezas', 'Juegos de aprendizaje']],
                ['Carrito Didáctico de Madera', 'ISA-CAR-003', 'Vehículo seguro y resistente para juego imaginativo.', 59, 'toy-car.svg', ['Juegos de imaginación', 'Juguetes de madera']],
                ['Osito Sensorial Bebé', 'ISA-BEBE-004', 'Peluche suave para estimulación temprana y apego seguro.', 69, 'toy-teddy.svg', ['Peluches', 'Juguetes sensoriales', 'Estimulación temprana']],
                ['Bloques de Construcción Creativa', 'ISA-BLOCK-005', 'Piezas coloridas para creatividad, lógica y trabajo en equipo.', 79, 'toy-blocks.svg', ['Bloques y construcción']],
                ['Dinosaurio Educativo', 'ISA-DINO-006', 'Figura infantil para aprender jugando y desarrollar imaginación.', 55, 'toy-dino.svg', ['Juegos de imaginación']],
            ];
            foreach ($six as [$name,$sku,$desc,$price,$image,$categories]) {
                $p = Product::create(['name' => $name, 'slug' => Str::slug($name), 'sku' => $sku, 'description' => $desc, 'price' => $price, 'stock' => null, 'active' => true, 'featured' => true, 'source' => 'catalogo-angular', 'legacy_data' => ['origin' => 'home.component.ts / DataInitializer.java', 'age_and_stock' => 'No presentes en el origen']]);
                $p->images()->create(['path' => '/assets/images/'.$image]);
                $p->categories()->sync(array_map(fn ($n) => $cats[$n], $categories));
            }
            foreach ([
                ['Juguetes que inspiran imaginación y aprendizaje', 'Pequeños descubrimientos. Grandes momentos juntos.', 'El mundo de Isablue', 'main', 'toy-montessori.svg'],
                ['Un mundo por descubrir', 'Explora nuestros juguetes sensoriales y encuentra su próximo compañero de juegos.', 'Estimulación temprana', 'baby', 'toy-teddy.svg'],
                ['La imaginación empieza jugando', 'Rompecabezas, bloques y juguetes didácticos para cada nueva aventura.', 'Montessori y creatividad', 'montessori', 'toy-puzzle.svg'],
            ] as $i => [$title,$subtitle,$eyebrow,$theme,$image]) {
                Banner::create(['title' => $title, 'subtitle' => $subtitle, 'eyebrow' => $eyebrow, 'theme' => $theme, 'image' => '/assets/images/'.$image, 'sort_order' => $i]);
            }
            Setting::create(['key' => 'delivery_info', 'value' => 'Consulta las opciones de entrega disponibles al realizar tu pedido.']);
        });
        foreach ($data as $table => $rows) {
            $count = DB::table('legacy_records')->where('source_table', $table)->count();
            if ($count !== count($rows)) {
                throw new \RuntimeException('Recuento incompatible: '.$table);
            }$this->info($table.': '.$count.' registros preservados');
        }
        $this->info('Importación completada. Los seis juguetes requieren edad documentada y conteo de stock.');

        return 0;
    }
}
