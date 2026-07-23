import { Component, OnInit } from '@angular/core';
import { SessionStorageService } from 'src/app/services/session-storage.service';
import { PaymentResult } from 'src/app/services/payment.service';
import { environment } from 'src/environments/environment';

@Component({
  selector: 'app-payment-success',
  templateUrl: './payment-success.component.html',
  styleUrls: ['./payment-success.component.css']
})
export class PaymentSuccessComponent implements OnInit {
  payment: PaymentResult | null = null;
  readonly whatsapp = environment.store?.whatsapp || '';
  get whatsappLink(): string {
    return this.whatsapp ? 'https://wa.me/' + this.whatsapp : '';
  }

  constructor(private sessionStorage: SessionStorageService) {}

  ngOnInit(): void {
    this.payment = this.sessionStorage.getItem('lastPayment');
    this.sessionStorage.removeItem('order');
  }
}
