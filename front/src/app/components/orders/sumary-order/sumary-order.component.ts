import { Component, NgZone, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { ItemCart } from 'src/app/common/item-cart';
import { Order } from 'src/app/common/order';
import { OrderProduct } from 'src/app/common/order-product';
import { OrderState } from 'src/app/common/order-state';
import { CartService } from 'src/app/services/cart.service';
import { OrderService } from 'src/app/services/order.service';
import { PaymentService, PaymentResult } from 'src/app/services/payment.service';
import { SessionStorageService } from 'src/app/services/session-storage.service';
import { UserService } from 'src/app/services/user.service';

@Component({ selector: 'app-sumary-order', templateUrl: './sumary-order.component.html', styleUrls: ['./sumary-order.component.css'] })
export class SumaryOrderComponent implements OnInit {
  items: ItemCart[] = [];
  totalCart = 0;
  firstName = ''; lastName = ''; email = ''; address = '';
  userId = 0;
  processing = false;

  method: 'yape' | 'card' = 'yape';

  // Yape
  yape = { number: '985436488', name: 'Isablue Juguetes' };
  operationNumber = '';
  private readonly whatsapp = '51920097746';

  // Tarjeta
  showCard = false;
  culqiConfigured = false;
  publicKey = '';
  private pendingOrderId = 0;
  card = { name: '', number: '', exp: '', cvv: '' };

  constructor(
    private cartService: CartService,
    private userService: UserService,
    private orderService: OrderService,
    private paymentService: PaymentService,
    private session: SessionStorageService,
    private toastr: ToastrService,
    private router: Router,
    private zone: NgZone
  ) {}

  ngOnInit(): void {
    this.refresh();
    this.userId = this.session.getItem('token')?.id || 0;
    this.userService.getCurrentUser().subscribe({
      next: data => { this.firstName = data.firstName; this.lastName = data.lastName; this.email = data.email; this.address = data.address; },
      error: () => {
        // Sesión inválida/vencida: llevamos al login para no dejar al cliente con un error confuso.
        this.session.clear();
        this.toastr.info('Vuelve a iniciar sesión para completar tu compra.');
        this.router.navigate(['/user/login'], { queryParams: { returnUrl: '/cart/sumary' } });
      }
    });
    this.paymentService.getConfig().subscribe({
      next: c => { this.culqiConfigured = c.culqiConfigured; this.publicKey = c.publicKey || ''; },
      error: () => { this.culqiConfigured = false; }
    });
    this.paymentService.getYapeInfo().subscribe({
      next: y => { if (y?.number) { this.yape = y; } },
      error: () => {}
    });
  }

  setMethod(m: 'yape' | 'card'): void { this.method = m; this.showCard = false; }

  // ---------- YAPE ----------
  payYape(): void {
    if (this.processing) return;
    if (!this.items.length) { this.toastr.info('Tu carrito está vacío.'); return; }
    this.processing = true;
    const itemsText = this.items.map(i => `- ${i.quantity} x ${i.productName}`).join('\n');
    const total = this.totalCart;
    this.createOrder(orderId => {
      this.paymentService.registerYape(orderId, this.operationNumber).subscribe({
        next: result => {
          this.cartService.clear();
          this.session.setItem('lastPayment', result);
          this.session.setItem('lastMethod', 'yape');
          this.openWhatsApp(orderId, total, itemsText);
          this.toastr.success('Pedido registrado. Te esperamos por WhatsApp.', '¡Gracias por tu compra!');
          this.router.navigate(['/payment/success']);
        },
        error: err => this.fail(err?.error?.message || 'No se pudo registrar tu pedido.')
      });
    });
  }

  private openWhatsApp(orderId: number, total: number, itemsText: string): void {
    const op = this.operationNumber ? `\nN° de operación Yape: ${this.operationNumber}` : '';
    const msg = `¡Hola Isablue! 🧸 Acabo de yapear mi pedido.\n\n` +
      `Pedido #${orderId}\nCliente: ${this.firstName} ${this.lastName}\n` +
      `Total: S/ ${total.toFixed(2)}${op}\n\nProductos:\n${itemsText}\n\n` +
      `Quedo atento(a) para coordinar la entrega. ¡Gracias!`;
    window.open(`https://wa.me/${this.whatsapp}?text=` + encodeURIComponent(msg), '_blank');
  }

  // ---------- TARJETA ----------
  openCard(): void {
    if (!this.items.length) { this.toastr.info('Tu carrito está vacío.'); return; }
    if (this.culqiConfigured && this.publicKey && (window as any).Culqi) {
      this.payWithCulqi();
    } else {
      this.card.name = `${this.firstName} ${this.lastName}`.trim();
      this.showCard = true;
    }
  }

  useTestCard(): void {
    this.card = { name: this.card.name || 'Cliente Demo', number: '4111 1111 1111 1111', exp: '09/28', cvv: '123' };
  }

  pay(): void {
    if (this.processing) return;
    if (!this.items.length) { this.toastr.info('Tu carrito está vacío.'); return; }
    if (!this.card.number || !this.card.exp || !this.card.cvv) { this.toastr.error('Completa los datos de la tarjeta.'); return; }
    this.processing = true;
    this.createOrder(orderId => this.doCharge(orderId, null));
  }

  private payWithCulqi(): void {
    if (this.processing) return;
    this.processing = true;
    this.createOrder(orderId => {
      this.pendingOrderId = orderId;
      const C = (window as any).Culqi;
      C.publicKey = this.publicKey;
      C.settings({ title: 'Isablue', currency: 'PEN', amount: Math.round(this.totalCart * 100) });
      (window as any).culqi = () => this.zone.run(() => this.onCulqi());
      C.open();
      this.processing = false;
    });
  }

  private onCulqi(): void {
    const C = (window as any).Culqi;
    if (C.token) {
      this.processing = true;
      this.doCharge(this.pendingOrderId, C.token.id);
    } else if (C.error) {
      this.toastr.error(C.error.user_message || 'El pago no se completó.');
    }
  }

  private doCharge(orderId: number, token: string | null): void {
    this.paymentService.charge(orderId, token).subscribe({
      next: (result: PaymentResult) => {
        this.cartService.clear();
        this.session.setItem('lastPayment', result);
        this.session.setItem('lastMethod', 'card');
        this.toastr.success('Pago aprobado y boleta generada.', '¡Gracias por tu compra!');
        this.router.navigate(['/payment/success']);
      },
      error: err => this.fail(err?.error?.message || 'No se pudo procesar el pago.')
    });
  }

  // ---------- comunes ----------
  private createOrder(onCreated: (orderId: number) => void): void {
    const products = this.items.map(i => new OrderProduct(null, i.productId, i.quantity, i.price));
    const order = new Order(null, new Date(), products, this.userId, OrderState.CANCELLED);
    this.orderService.createOrder(order).subscribe({
      next: created => onCreated(created.id!),
      error: () => this.fail('No pudimos preparar tu orden. Revisa tus datos.')
    });
  }

  deleteItemCart(id: number): void { this.cartService.deleteItemCart(id); this.refresh(); }
  private refresh(): void { this.items = [...this.cartService.convertToListFromMap()]; this.totalCart = this.cartService.totalCart(); }
  private fail(message: string): void { this.processing = false; this.toastr.error(message + ' Inténtalo nuevamente.'); }
}
