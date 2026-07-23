import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { environment } from 'src/environments/environment';
import { DataPayment } from '../common/data-payment';
import { Observable } from 'rxjs';
import { UrlPaypalResponse } from '../common/url-paypal-response';
import { SessionStorageService } from './session-storage.service';

@Injectable({ providedIn: 'root' })
export class PaymentService {
  private apiUrl: string = environment.apiUrl + '/payments';

  constructor(private http: HttpClient, private sessionStorage: SessionStorageService) { }

  private authHeaders(): HttpHeaders {
    const session = this.sessionStorage.getItem('token');
    return new HttpHeaders({ 'Content-Type': 'application/json', 'Authorization': session?.token || '' });
  }

  getUrlPaypalPayment(dataPayment: DataPayment): Observable<UrlPaypalResponse> {
    return this.http.post<UrlPaypalResponse>(this.apiUrl, dataPayment, { headers: this.authHeaders() });
  }

  /** Indica si hay llaves reales de Culqi/Nubefact o estamos en modo prueba, y la llave pública de Culqi. */
  getConfig(): Observable<{ culqiConfigured: boolean; boletaConfigured: boolean; publicKey: string }> {
    return this.http.get<{ culqiConfigured: boolean; boletaConfigured: boolean; publicKey: string }>(
      this.apiUrl + '/culqi/config', { headers: this.authHeaders() });
  }

  /** Cobra la orden con tarjeta. token = token de Culqi.js, o null en modo prueba. */
  charge(orderId: number, token: string | null): Observable<PaymentResult> {
    return this.http.post<PaymentResult>(this.apiUrl + '/culqi/charge', { orderId, token }, { headers: this.authHeaders() });
  }

  /** Lista de pagos para el administrador. */
  getAdminPayments(): Observable<PaymentResult[]> {
    return this.http.get<PaymentResult[]>(environment.apiUrl + '/admin/payments', { headers: this.authHeaders() });
  }

  /** Datos de Yape (número, nombre) y WhatsApp de la tienda para el checkout. */
  getYapeInfo(): Observable<{ number: string; name: string; whatsapp?: string }> {
    return this.http.get<{ number: string; name: string; whatsapp?: string }>(this.apiUrl + '/yape/info', { headers: this.authHeaders() });
  }

  /** Registra un pedido pagado por Yape (queda "por confirmar"). */
  registerYape(orderId: number, operationNumber: string): Observable<PaymentResult> {
    return this.http.post<PaymentResult>(this.apiUrl + '/yape/register', { orderId, operationNumber }, { headers: this.authHeaders() });
  }
}

export interface PaymentResult {
  id: number;
  orderId: number;
  customerName: string;
  customerEmail: string;
  amount: number;
  currency: string;
  status: string;
  method: string;
  reference: string;
  boletaSerie: string;
  boletaNumber: string;
  boletaUrl: string;
  createdAt: string;
}
