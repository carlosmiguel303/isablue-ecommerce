<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DemoData extends Command
{
    protected $signature = 'isablue:demo';

    protected $description = 'Carga única de datos simulados autorizados para probar los seis juguetes.';

    public function handle(): int
    {
        if (Setting::valueOf('demo_seeded') === '1') {
            $this->warn('La prueba ya fue cargada; no se sobrescriben los ajustes.');

            return 0;
        }DB::transaction(function () {
            $rows = ['ISA-MONT-001' => [36, 71, 20], 'ISA-PUZ-002' => [36, 71, 15], 'ISA-CAR-003' => [12, 59, 18], 'ISA-BEBE-004' => [0, 35, 25], 'ISA-BLOCK-005' => [36, 71, 12], 'ISA-DINO-006' => [60, 71, 10]];
            foreach ($rows as $sku => [$min,$max,$stock]) {
                $p = Product::where('sku', $sku)->where('source', 'catalogo-angular')->lockForUpdate()->firstOrFail();
                StockMovement::create(['product_id' => $p->id, 'before' => $p->stock, 'change' => $stock - ($p->stock ?? 0), 'after' => $stock, 'reason' => 'DATOS DE PRUEBA autorizados; no representan conteo físico']);
                $p->update(['age_min_months' => $min, 'age_max_months' => $max, 'age_source' => 'SIMULADO PARA PRUEBAS. No es recomendación del fabricante.', 'stock' => $stock, 'demo_data' => true]);
            }
            ShippingMethod::create(['name' => 'Entrega de prueba — Lima', 'description' => 'Tarifa simulada para recorrer el checkout. No representa un servicio real.', 'price' => 10, 'active' => true, 'demo_data' => true]);
            ShippingMethod::create(['name' => 'Recojo de prueba', 'description' => 'Opción simulada gratuita. No acudir a una dirección real.', 'price' => 0, 'active' => true, 'demo_data' => true]);
            Setting::updateOrCreate(['key' => 'demo_mode'], ['value' => '1']);
            Setting::updateOrCreate(['key' => 'demo_seeded'], ['value' => '1']);
        });
        $this->info('Seis productos con datos simulados y dos opciones de entrega de prueba.');

        return 0;
    }
}
