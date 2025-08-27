import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { Usuario, LoginRequest, LoginResponse } from '../models';
import { ApiService } from '../services/api.service';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSubject = new BehaviorSubject<Usuario | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();

  constructor(private apiService: ApiService) {
    this.loadStoredUser();
  }

  private loadStoredUser(): void {
    const token = localStorage.getItem('accessToken');
    const userStr = localStorage.getItem('currentUser');
    
    if (token && userStr) {
      try {
        const user = JSON.parse(userStr);
        this.currentUserSubject.next(user);
      } catch {
        this.clearStoredData();
      }
    }
  }

  private clearStoredData(): void {
    localStorage.removeItem('accessToken');
    localStorage.removeItem('currentUser');
    this.currentUserSubject.next(null);
  }

  login(email: string, password: string): Observable<LoginResponse> {
    const request: LoginRequest = { email, password };
    
    return this.apiService.post<LoginResponse>('/auth/login', request).pipe(
      tap(response => {
        localStorage.setItem('accessToken', response.accessToken);
        localStorage.setItem('currentUser', JSON.stringify(response.usuario));
        this.currentUserSubject.next(response.usuario);
      })
    );
  }

  logout(): void {
    this.clearStoredData();
  }

  isAuthenticated(): boolean {
    return !!this.getToken();
  }

  getCurrentUser(): Usuario | null {
    return this.currentUserSubject.value;
  }

  hasRole(role: string): boolean {
    const user = this.currentUserSubject.value;
    return user?.rol === role;
  }

  hasAnyRole(roles: string[]): boolean {
    const user = this.currentUserSubject.value;
    return user ? roles.includes(user.rol) : false;
  }

  getToken(): string | null {
    return localStorage.getItem('accessToken');
  }
}
