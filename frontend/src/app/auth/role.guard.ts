import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from './auth.service';

export const roleGuard = (allowedRoles: string[]) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (authService.hasAnyRole(allowedRoles)) {
    return true;
  }

  router.navigate(['/busetas']);
  return false;
};
