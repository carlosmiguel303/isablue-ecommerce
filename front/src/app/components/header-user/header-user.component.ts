import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { SessionStorageService } from 'src/app/services/session-storage.service';

@Component({
  selector: 'app-header-user',
  templateUrl: './header-user.component.html',
  styleUrls: ['./header-user.component.css']
})
export class HeaderUserComponent {
  q = '';
  constructor(private router: Router, private session: SessionStorageService) {}

  buscar(): void {
    this.router.navigate(['/'], { queryParams: { q: this.q }});
  }

  isLogged(): boolean { return this.session.getItem('token') != null; }
  isAdmin(): boolean { return this.session.getItem('token')?.type === 'ADMIN'; }
  logout(): void { this.session.clear(); this.router.navigate(['/user/login']); }
}
