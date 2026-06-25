import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { SessionStorageService } from '../services/session-storage.service';

export const authGuard: CanActivateFn = (route, state) => {
  const session = inject(SessionStorageService);
  const router = inject(Router);
  const token = session.getItem('token');

  if (!token) {
    return router.createUrlTree(['/user/login'], { queryParams: { returnUrl: state.url }});
  }

  const requiredRole = route.data?.['role'];
  if (requiredRole && token.type !== requiredRole) {
    return router.createUrlTree(['/']);
  }

  return true;
};
