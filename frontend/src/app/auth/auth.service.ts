import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { Usuario, LoginRequest, LoginResponse, UserInfo } from '../models';
import { ApiService } from '../services/api.service';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSubject = new BehaviorSubject<UserInfo | null>(null);
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

  login(request: LoginRequest): Observable<LoginResponse> {
    return this.apiService.post<LoginResponse>('/auth/login', request).pipe(
      tap(response => {
        localStorage.setItem('accessToken', response.accessToken);
        localStorage.setItem('currentUser', JSON.stringify(response.user));
        localStorage.setItem('tenant', response.tenant);
        this.currentUserSubject.next(response.user);
      })
    );
  }

  logout(): void {
    this.clearStoredData();
  }

  isAuthenticated(): boolean {
    return !!this.getToken();
  }

  getCurrentUser(): UserInfo | null {
    return this.currentUserSubject.value;
  }

  hasRole(role: string): boolean {
    const user = this.currentUserSubject.value;
    // Note: UserInfo doesn't have role, so this will always return false
    // In a real implementation, you might want to include role in UserInfo
    return false;
  }

  hasAnyRole(roles: string[]): boolean {
    const user = this.currentUserSubject.value;
    // Note: UserInfo doesn't have role, so this will always return false
    // In a real implementation, you might want to include role in UserInfo
    return false;
  }

  getToken(): string | null {
    return localStorage.getItem('accessToken');
  }
}
