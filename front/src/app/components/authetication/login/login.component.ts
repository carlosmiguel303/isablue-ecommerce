import { Component, OnInit } from '@angular/core';
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
export class LoginComponent implements OnInit{
  username : string = '';
  password : string = '';
  loading = false;
  errorMessage = '';

  constructor(private authentication : AuthenticationService,
    private sessionStorage : SessionStorageService,
    private router: Router,
    private route: ActivatedRoute,
    private toastr: ToastrService
  ){}

  ngOnInit(): void {
    const token = this.sessionStorage.getItem('token');
    if (token) {
      this.router.navigate([token.type === 'ADMIN' ? '/admin/product' : '/']);
    }
  }

  login(){
    this.errorMessage = '';
    if(!this.username || !this.password){
      this.errorMessage = 'Ingresa tu correo y contraseña.';
      return;
    }

    this.loading = true;
    let userDto = new Userdto(this.username, this.password);
    this.authentication.login(userDto).subscribe({
      next: token => {
        this.sessionStorage.setItem('token', token);
        this.toastr.success('Bienvenido a IsaBlue Juguetes', 'Ingreso correcto');
        const returnUrl = this.route.snapshot.queryParamMap.get('returnUrl');
        if(returnUrl){ this.router.navigateByUrl(returnUrl); return; }
        this.router.navigate([token.type == 'ADMIN' ? '/admin/product' : '/']);
      },
      error: err => {
        this.loading = false;
        this.errorMessage = err?.error?.message || 'Correo o contraseña incorrectos. Si no tienes cuenta, regístrate.';
        this.toastr.error(this.errorMessage, 'No se pudo ingresar');
      }
    });
  }
}
