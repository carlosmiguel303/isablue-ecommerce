import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler, HttpEvent, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { Router } from '@angular/router';
import { SessionStorageService } from '../services/session-storage.service';
import { ToastrService } from 'ngx-toastr';

/**
 * Si una petición autenticada falla porque la sesión está vencida o inválida,
 * cerramos la sesión y llevamos al login con un mensaje claro, en vez de dejar
 * al cliente con un error confuso.
 */
@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  private redirecting = false;

  constructor(
    private session: SessionStorageService,
    private router: Router,
    private toastr: ToastrService
  ) {}

  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    return next.handle(req).pipe(
      catchError((error: HttpErrorResponse) => {
        const isAuthCall = req.url.includes('/security/login') || req.url.includes('/security/register');
        const hasSession = !!this.session.getItem('token');
        if ((error.status === 401 || error.status === 403) && hasSession && !isAuthCall) {
          this.expireSession();
        }
        return throwError(() => error);
      })
    );
  }

  private expireSession(): void {
    if (this.redirecting) { return; }
    this.redirecting = true;
    this.session.clear();
    this.toastr.info('Tu sesión expiró. Vuelve a iniciar sesión para continuar.');
    this.router.navigate(['/user/login']).finally(() => { this.redirecting = false; });
  }
}
