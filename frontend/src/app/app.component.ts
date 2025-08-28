import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet } from '@angular/router';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatMenuModule } from '@angular/material/menu';
import { MatDividerModule } from '@angular/material/divider';
import { AuthService } from './auth/auth.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [
    CommonModule,
    RouterOutlet,
    MatToolbarModule,
    MatButtonModule,
    MatIconModule,
    MatMenuModule,
    MatDividerModule
  ],
  template: `
    <mat-toolbar color="primary" *ngIf="authService.isAuthenticated()">
      <span>SIGAV - Alistamiento de Busetas</span>
      <span class="spacer"></span>
      
      <button mat-button [matMenuTriggerFor]="menu">
        <mat-icon>account_circle</mat-icon>
        {{ authService.getCurrentUser()?.name }}
      </button>
      
      <mat-menu #menu="matMenu">
        <button mat-menu-item routerLink="/busetas">
          <mat-icon>directions_bus</mat-icon>
          Busetas
        </button>
        <button mat-menu-item routerLink="/checklists/plantillas" *ngIf="authService.hasRole('Admin')">
          <mat-icon>checklist</mat-icon>
          Plantillas
        </button>
        <button mat-menu-item routerLink="/checklists/ejecucion/nueva" *ngIf="authService.hasRole('Admin') || authService.hasRole('Inspector')">
          <mat-icon>playlist_add_check</mat-icon>
          Nueva Ejecución
        </button>
        <button mat-menu-item routerLink="/historial">
          <mat-icon>history</mat-icon>
          Historial
        </button>
        <mat-divider></mat-divider>
        <button mat-menu-item (click)="logout()">
          <mat-icon>exit_to_app</mat-icon>
          Cerrar Sesión
        </button>
      </mat-menu>
    </mat-toolbar>
    
    <main>
      <router-outlet></router-outlet>
    </main>
  `,
  styles: [`
    .spacer {
      flex: 1 1 auto;
    }
    
    main {
      padding: 20px;
      max-width: 1200px;
      margin: 0 auto;
    }
  `]
})
export class AppComponent {
  constructor(
    public authService: AuthService,
    private router: Router
  ) {}

  logout() {
    this.authService.logout();
    this.router.navigate(['/login']);
  }
}
