import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { environment } from 'src/environments/environment';
import { DataPayment } from '../common/data-payment';
import { Observable } from 'rxjs';
import { UrlPaypalResponse } from '../common/url-paypal-response';
import { SessionStorageService } from './session-storage.service';

@Injectable({
  providedIn: 'root'
})
export class PaymentService {
  private apiUrl: string = environment.apiUrl + '/payments';

  constructor(
    private http: HttpClient,
    private sessionStorage: SessionStorageService
  ) { }

  getUrlPaypalPayment(dataPayment: DataPayment): Observable<UrlPaypalResponse> {
    const session = this.sessionStorage.getItem('token');

    const headers = new HttpHeaders({
      'Content-Type': 'application/json',
      'Authorization': session?.token || ''
    });

    return this.http.post<UrlPaypalResponse>(this.apiUrl, dataPayment, { headers });
  }
}