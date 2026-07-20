import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { SessionStorageService } from './session-storage.service';

export interface Promo {
  id: number;
  title: string;
  subtitle: string;
  cta: string;
  link: string;
  image: string;   // URL o dataURL opcional
  bg: string;      // fondo (color o gradiente CSS)
  text: string;    // color del texto
}

/**
 * Promociones del banner. Se guardan en la base de datos (backend) para que
 * TODAS las visitas las vean, no solo el navegador del administrador.
 */
@Injectable({ providedIn: 'root' })
export class PromoService {
  private pub = environment.apiUrl + '/home/promos';
  private adm = environment.apiUrl + '/admin/promos';

  constructor(private http: HttpClient, private session: SessionStorageService) {}

  private headers(): HttpHeaders {
    return new HttpHeaders({ 'Content-Type': 'application/json', 'Authorization': this.session.getItem('token')?.token || '' });
  }

  getAll(): Observable<Promo[]> {
    return this.http.get<Promo[]>(this.pub);
  }

  add(promo: Omit<Promo, 'id'>): Observable<Promo> {
    return this.http.post<Promo>(this.adm, promo, { headers: this.headers() });
  }

  update(promo: Promo): Observable<Promo> {
    return this.http.put<Promo>(`${this.adm}/${promo.id}`, promo, { headers: this.headers() });
  }

  remove(id: number): Observable<any> {
    return this.http.delete(`${this.adm}/${id}`, { headers: this.headers() });
  }

  reset(): Observable<Promo[]> {
    return this.http.post<Promo[]>(`${this.adm}/reset`, {}, { headers: this.headers() });
  }
}
