import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { environment } from 'src/environments/environment';
import { User } from '../common/user';
import { Observable, of, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { Userdto } from '../common/userdto';
import { Jwtclient } from '../common/jwtclient';

/**
 * Autenticación con backend real cuando está disponible.
 * Si el backend no responde (modo demostración / presentación al cliente),
 * usa usuarios locales para que el login siempre funcione.
 */
interface LocalUser { id: number; email: string; password: string; type: string; firstName: string; }

@Injectable({ providedIn: 'root' })
export class AuthenticationService {
  private apiUrl: string = environment.apiUrl + '/security';
  private readonly STORE = 'isablue_users';

  constructor(private httpClient: HttpClient) { this.seedDemoUsers(); }

  register(user: User): Observable<User> {
    return this.httpClient.post<User>(this.apiUrl + '/register', user).pipe(
      catchError(() => this.localRegister(user))
    );
  }

  /** Llave pública de Google para mostrar el botón. */
  googleConfig(): Observable<{ clientId: string }> {
    return this.httpClient.get<{ clientId: string }>(this.apiUrl + '/google/config');
  }

  /** Inicia sesión con el token de Google (crea la cuenta si es nueva). */
  googleLogin(credential: string): Observable<Jwtclient> {
    return this.httpClient.post<Jwtclient>(this.apiUrl + '/google', { credential });
  }

  login(userDto: Userdto): Observable<Jwtclient> {
    return this.httpClient.post<Jwtclient>(this.apiUrl + '/login', userDto).pipe(
      catchError(() => this.localLogin(userDto))
    );
  }

  // ---------- Modo demostración (local) ----------
  private seedDemoUsers(): void {
    if (this.readUsers().length) { return; }
    this.writeUsers([
      { id: 1, email: 'admin@isablue.pe', password: 'admin123', type: 'ADMIN', firstName: 'Administrador' },
      { id: 2, email: 'cliente@isablue.pe', password: 'cliente123', type: 'USER', firstName: 'Cliente' }
    ]);
  }

  private localLogin(dto: Userdto): Observable<Jwtclient> {
    const email = (dto.username || '').trim().toLowerCase();
    const found = this.readUsers().find(u => u.email.toLowerCase() === email && u.password === dto.password);
    if (!found) {
      return throwError(() => ({ error: { message: 'Correo o contraseña incorrectos. Si no tienes cuenta, regístrate.' } }));
    }
    return of(new Jwtclient(found.id, 'demo-' + btoa(found.email) + '-' + Date.now(), found.type));
  }

  private localRegister(user: User): Observable<User> {
    const users = this.readUsers();
    const email = (user.email || '').trim().toLowerCase();
    if (users.some(u => u.email.toLowerCase() === email)) {
      return throwError(() => ({ error: { message: 'Ese correo ya está registrado. Inicia sesión.' } }));
    }
    const id = users.reduce((m, u) => Math.max(m, u.id), 0) + 1;
    users.push({ id, email, password: user.password, type: 'USER', firstName: user.firstName || 'Cliente' });
    this.writeUsers(users);
    return of(user);
  }

  private readUsers(): LocalUser[] {
    try { return JSON.parse(localStorage.getItem(this.STORE) || '[]'); } catch { return []; }
  }
  private writeUsers(users: LocalUser[]): void {
    localStorage.setItem(this.STORE, JSON.stringify(users));
  }
}
