import { Component, OnInit } from '@angular/core';
import { ToastrService } from 'ngx-toastr';
import { PaymentResult, PaymentService } from 'src/app/services/payment.service';
import { OrderService } from 'src/app/services/order.service';
import { HomeService } from 'src/app/services/home.service';
import { environment } from 'src/environments/environment';

@Component({
  selector: 'app-admin-payments',
  templateUrl: './admin-payments.component.html',
  styleUrls: ['./admin-payments.component.css']
})
export class AdminPaymentsComponent implements OnInit {
  payments: PaymentResult[] = [];
  loading = true;
  private productNames = new Map<number, string>();
  private readonly whatsapp = environment.store?.whatsapp || '';

  constructor(
    private paymentService: PaymentService,
    private orderService: OrderService,
    private homeService: HomeService,
    private toastr: ToastrService
  ) {}

  ngOnInit(): void {
    this.homeService.getProducts().subscribe({
      next: prods => prods.forEach(p => this.productNames.set(p.id, p.name)),
      error: () => {}
    });
    this.paymentService.getAdminPayments().subscribe({
      next: p => { this.payments = p; this.loading = false; },
      error: () => { this.loading = false; this.toastr.error('No se pudieron cargar los pagos.'); }
    });
  }

  get totalRecaudado(): number { return this.payments.reduce((s, p) => s + Number(p.amount), 0); }

  /** Imprime un ticket de despacho (formato POS 80 mm) para poner en la caja del pedido. */
  imprimir(p: PaymentResult): void {
    this.orderService.getOrderById(p.orderId).subscribe({
      next: (order: any) => this.printTicket(p, order?.orderProducts || []),
      error: () => this.printTicket(p, [])
    });
  }

  private printTicket(p: PaymentResult, items: any[]): void {
    const fecha = new Date(p.createdAt).toLocaleString('es-PE');
    const filas = items.map(it => {
      const nombre = this.productNames.get(it.productId) || ('Producto #' + it.productId);
      const cant = Number(it.quantity);
      const precio = Number(it.price);
      const sub = (cant * precio).toFixed(2);
      return `<tr><td>${cant} x</td><td>${nombre}</td><td class="r">S/ ${sub}</td></tr>`;
    }).join('') || `<tr><td colspan="3">Pedido #${p.orderId}</td></tr>`;

    const oficial = p.boletaUrl
      ? `<div class="note">Boleta electrónica SUNAT enviada al correo del cliente.</div>`
      : `<div class="note">Comprobante interno (activar Nubefact para boleta SUNAT oficial).</div>`;

    const html = `<!doctype html><html><head><meta charset="utf-8"><title>Boleta ${p.boletaSerie}-${p.boletaNumber}</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{width:80mm;padding:6mm 4mm;font-family:'Consolas','Courier New',monospace;color:#000;font-size:12px;line-height:1.35}
  .c{text-align:center}.r{text-align:right}.b{font-weight:bold}
  .brand{font-size:18px;font-weight:bold;letter-spacing:1px}
  .sub{font-size:10px;letter-spacing:2px}
  hr{border:0;border-top:1px dashed #000;margin:6px 0}
  table{width:100%;border-collapse:collapse}
  td{vertical-align:top;padding:1px 0;font-size:11px}
  .tot{font-size:15px;font-weight:bold}
  .meta{font-size:11px}
</style></head><body>
  <div class="c brand">ISABLUE</div>
  <div class="c sub">JUGUETES Y ACCESORIOS</div>
  <div class="c meta">${this.whatsapp ? 'WhatsApp ' + this.whatsapp : ''}</div>
  <hr>
  <div class="c b">BOLETA DE VENTA</div>
  <div class="c">${p.boletaSerie}-${p.boletaNumber}</div>
  <hr>
  <div class="meta">Fecha: ${fecha}</div>
  <div class="meta">Cliente: ${p.customerName}</div>
  <div class="meta">Pedido: #${p.orderId}</div>
  <div class="meta">Pago: ${p.method} · ${p.reference}</div>
  <hr>
  <table>${filas}</table>
  <hr>
  <table><tr><td class="tot">TOTAL</td><td class="tot r">S/ ${Number(p.amount).toFixed(2)}</td></tr></table>
  <hr>
  ${oficial}
  <div class="c" style="margin-top:8px">¡Gracias por tu compra! 🧸</div>
  <div class="c meta">facebook.com/isabluejuguetes</div>
</body></html>`;

    const w = window.open('', 'ticket_isablue', 'width=380,height=640');
    if (!w) { this.toastr.error('Permite las ventanas emergentes para imprimir.'); return; }
    w.document.open();
    w.document.write(html);
    w.document.close();
    w.focus();
    setTimeout(() => { w.print(); }, 350);
  }
}
