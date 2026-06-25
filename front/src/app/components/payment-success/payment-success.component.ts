import { Component, OnInit } from '@angular/core';
import { SessionStorageService } from 'src/app/services/session-storage.service';

@Component({
  selector: 'app-payment-success',
  templateUrl: './payment-success.component.html',
  styleUrls: ['./payment-success.component.css']
})
export class PaymentSuccessComponent implements OnInit {
  constructor(private sessionStorage: SessionStorageService) {}

  ngOnInit(): void {
    this.sessionStorage.removeItem('order');
  }
}
