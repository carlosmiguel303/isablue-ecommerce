import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { environment } from 'src/environments/environment';
import { Observable } from 'rxjs';
import { User } from '../common/user';
import { HeaderService } from './header.service';

@Injectable({ providedIn: 'root' })
export class UserService {
  private apiUrl: string = environment.apiUrl + '/users';

  constructor(private httpClient: HttpClient, private headerService: HeaderService) {}

  getCurrentUser(): Observable<User> {
    return this.httpClient.get<User>(`${this.apiUrl}/me`, { headers: this.headerService.headers });
  }
}
