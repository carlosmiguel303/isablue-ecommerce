import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { SessionStorageService } from './session-storage.service';

export interface InfoPage { id: number; pageKey: string; title: string; content: string; }

@Injectable({ providedIn: 'root' })
export class InfoService {
  private pub = environment.apiUrl + '/home/info';
  private adm = environment.apiUrl + '/admin/info';

  constructor(private http: HttpClient, private session: SessionStorageService) {}

  private headers(): HttpHeaders {
    return new HttpHeaders({ 'Content-Type': 'application/json', 'Authorization': this.session.getItem('token')?.token || '' });
  }

  get(key: string): Observable<InfoPage> {
    return this.http.get<InfoPage>(`${this.pub}/${key}`);
  }

  list(): Observable<InfoPage[]> {
    return this.http.get<InfoPage[]>(this.adm, { headers: this.headers() });
  }

  update(key: string, data: { title: string; content: string }): Observable<InfoPage> {
    return this.http.put<InfoPage>(`${this.adm}/${key}`, data, { headers: this.headers() });
  }
}
