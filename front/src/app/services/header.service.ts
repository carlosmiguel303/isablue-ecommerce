import { HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { SessionStorageService } from './session-storage.service';

@Injectable({ providedIn: 'root' })
export class HeaderService {
  constructor(private sessionStorage: SessionStorageService) {}

  get headers(): HttpHeaders {
    const session = this.sessionStorage.getItem('token');
    return session?.token
      ? new HttpHeaders({ Authorization: session.token })
      : new HttpHeaders();
  }
}
