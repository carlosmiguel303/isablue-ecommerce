import { Component, NgZone, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { Userdto } from 'src/app/common/userdto';
import { AuthenticationService } from 'src/app/services/authentication.service';
import { SessionStorageService } from 'src/app/services/session-storage.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css']
})
export class LoginComponent implements OnInit {
  username: string = '';
  password: string = '';
  loading = false;
  errorMessage = '';
  googleClientId = '';
  private googleInited = false;

  constructor(private authentication: AuthenticationService,
    private sessionStorage: SessionStorageService,
    private router: Router,
    private route: ActivatedRoute,
    private toastr: ToastrService,
    private zone: NgZone
  ) {}

  ngOnInit(): void {
    const token = this.sessionStorage.getItem('token');
    if (token) {
      this.router.navigate([token.type === 'ADMIN' ? '/admin/product' : '/']);
      return;
    }
    this.authentication.googleConfig().subscribe({
      next: c => { if (c?.clientId) { this.googleClientId = c.clientId; this.initGoogle(); } },
      error: () => {}
    });
  }

  login() {
    this.errorMessage = '';
    if (!this.username || !this.password) {
      this.errorMessage = 'Ingresa tu correo y contraseña.';
      return;
    }
    this.loading = true;
    let userDto = new Userdto(this.username, this.password);
    this.authentication.login(userDto).subscribe({
      next: token => this.enter(token, 'Bienvenido a Isablue', 'Ingreso correcto'),
      error: err => {
        this.loading = false;
        this.errorMessage = err?.error?.message || 'Correo o contraseña incorrectos. Si no tienes cuenta, regístrate.';
        this.toastr.error(this.errorMessage, 'No se pudo ingresar');
      }
    });
  }

  private initGoogle(attempt = 0): void {
    const g = (window as any).google;
    const el = document.getElementById('googleBtn');
    // Espera a que la librería de Google Y el espacio del botón estén listos.
    if (!g?.accounts?.id || !el) {
      if (attempt < 40) { setTimeout(() => this.initGoogle(attempt + 1), 300); }
      return;
    }
    if (!this.googleInited) {
      g.accounts.id.initialize({
        client_id: this.googleClientId,
        callback: (resp: any) => this.zone.run(() => this.onGoogle(resp))
      });
      this.googleInited = true;
    }
    el.innerHTML = '';
    g.accounts.id.renderButton(el, { theme: 'outline', size: 'large', width: '320', text: 'continue_with', shape: 'pill', logo_alignment: 'center' });
  }

  private onGoogle(resp: any): void {
    if (!resp?.credential) { return; }
    this.loading = true;
    this.authentication.googleLogin(resp.credential).subscribe({
      next: token => this.enter(token, 'Bienvenido a Isablue', 'Ingreso con Google'),
      error: err => {
        this.loading = false;
        this.errorMessage = err?.error?.message || 'No se pudo ingresar con Google.';
        this.toastr.error(this.errorMessage, 'Google');
      }
    });
  }

  private enter(token: any, msg: string, title: string): void {
    this.sessionStorage.setItem('token', token);
    this.toastr.success(msg, title);
    const returnUrl = this.route.snapshot.queryParamMap.get('returnUrl');
    if (returnUrl) { this.router.navigateByUrl(returnUrl); return; }
    this.router.navigate([token.type === 'ADMIN' ? '/admin/product' : '/']);
  }
}
