import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { environment } from 'src/environments/environment';
import { Observable } from 'rxjs';
import { Product } from '../common/product';

export interface Categoria { id: number; name: string; }

@Injectable({ providedIn: 'root' })
export class HomeService {
  private apiUrl: string = environment.apiUrl + '/home';

  constructor(private httpClient: HttpClient) { }

  getProducts(): Observable<Product[]> {
    return this.httpClient.get<Product[]>(this.apiUrl);
  }

  getProductById(id: number): Observable<Product> {
    return this.httpClient.get<Product>(this.apiUrl + '/' + id);
  }

  getCategories(): Observable<Categoria[]> {
    return this.httpClient.get<Categoria[]>(this.apiUrl + '/categories');
  }
}
