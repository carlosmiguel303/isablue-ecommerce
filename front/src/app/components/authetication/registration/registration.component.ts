import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { User } from 'src/app/common/user';
import { UserType } from 'src/app/common/user-type';
import { AuthenticationService } from 'src/app/services/authentication.service';

@Component({
  selector: 'app-registration',
  templateUrl: './registration.component.html',
  styleUrls: ['./registration.component.css']
})
export class RegistrationComponent implements OnInit{
  username : string = ''; name : string = ''; surname : string = ''; email : string = '';
  address : string = ''; cellphone : string = ''; password : string = ''; userType : string = '';
  loading = false; errorMessage = '';

  ngOnInit(): void {}

  constructor(private authetication : AuthenticationService, private router : Router, private toastr:ToastrService){}

  register(){
    this.errorMessage = '';
    if(!this.name || !this.surname || !this.email || !this.password){
      this.errorMessage = 'Completa nombre, apellido, correo y contraseña.';
      return;
    }
    this.loading = true;
    this.username = this.email;
    this.userType = UserType.USER;
    let user = new User(0, this.username, this.name, this.surname, this.email, this.address, this.cellphone, this.password, this.userType);
    this.authetication.register(user).subscribe({
      next: res => {
        this.toastr.success('Cuenta creada correctamente. Ahora inicia sesión.', 'Registro exitoso');
        this.router.navigate(['user/login']);
      },
      error: err => {
        this.loading = false;
        this.errorMessage = err?.error?.message || 'No se pudo registrar. Revisa los datos o usa otro correo.';
        this.toastr.error(this.errorMessage, 'Registro no realizado');
      }
    });
  }
}
