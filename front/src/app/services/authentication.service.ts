import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { environment } from 'src/environments/environment';
import { User } from '../common/user';
import { Observable } from 'rxjs';
import { Userdto } from '../common/userdto';
import { Jwtclient } from '../common/jwtclient';

/**
 * Autenticación real contra el backend (PostgreSQL).
 */
@Injectable({ providedIn: 'root' })
export class AuthenticationService {
  private apiUrl: string = environment.apiUrl + '/security';

  constructor(private httpClient: HttpClient) {}

  register(user: User): Observable<User> {
    return this.httpClient.post<User>(this.apiUrl + '/register', user);
  }

  login(userDto: Userdto): Observable<Jwtclient> {
    return this.httpClient.post<Jwtclient>(this.apiUrl + '/login', userDto);
  }

  /** Llave pública de Google para mostrar el botón. */
  googleConfig(): Observable<{ clientId: string }> {
    return this.httpClient.get<{ clientId: string }>(this.apiUrl + '/google/config');
  }

  /** Inicia sesión con el token de Google (crea la cuenta si es nueva). */
  googleLogin(credential: string): Observable<Jwtclient> {
    return this.httpClient.post<Jwtclient>(this.apiUrl + '/google', { credential });
  }
}
