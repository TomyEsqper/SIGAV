import { Injectable } from '@angular/core';
import { CanActivate, Router, ActivatedRouteSnapshot } from '@angular/router';
import { AuthService } from '../services/auth.service';

@Injectable({
  providedIn: 'root'
})
export class RoleGuard implements CanActivate {
  constructor(private authService: AuthService, private router: Router) {}

  canActivate(route: ActivatedRouteSnapshot): boolean {
    const requiredRole = route.data['role'] as string;
    
    if (!requiredRole) {
      return true;
    }

    if (this.authService.hasRole(requiredRole)) {
      return true;
    }

    this.router.navigate(['/']);
    return false;
  }
}
