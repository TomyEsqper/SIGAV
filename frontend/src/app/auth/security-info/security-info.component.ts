import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatChipsModule } from '@angular/material/chips';
import { AuthService } from '../auth.service';

@Component({
  selector: 'app-security-info',
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatIconModule,
    MatButtonModule,
    MatChipsModule
  ],
  template: `
    <div class="security-info-container">
      <mat-card>
        <mat-card-header>
          <mat-card-title>
            <mat-icon>security</mat-icon>
            Información de Seguridad
          </mat-card-title>
        </mat-card-header>
        
        <mat-card-content>
          <div class="security-section">
            <h3>Último Acceso</h3>
            <p>{{ lastLoginInfo }}</p>
          </div>
          
          <div class="security-section">
            <h3>Dispositivos Activos</h3>
            <div class="device-list">
              <div *ngFor="let device of devices" class="device-item">
                <mat-icon>{{ getDeviceIcon(device.type) }}</mat-icon>
                <div class="device-info">
                  <strong>{{ device.name }}</strong>
                  <span>{{ device.location || 'Ubicación desconocida' }}</span>
                  <span class="last-access">Último acceso: {{ device.lastAccess | date:'short' }}</span>
                </div>
                <mat-chip *ngIf="device.trusted" color="primary" selected>
                  Confiable
                </mat-chip>
              </div>
            </div>
          </div>
          
          <div class="security-section">
            <h3>Actividad Reciente</h3>
            <div class="activity-list">
              <div *ngFor="let activity of recentActivity" class="activity-item">
                <mat-icon [class]="getActivityIcon(activity.type)">
                  {{ getActivityIcon(activity.type) }}
                </mat-icon>
                <div class="activity-info">
                  <span>{{ activity.description }}</span>
                  <span class="activity-time">{{ activity.timestamp | date:'short' }}</span>
                </div>
              </div>
            </div>
          </div>
        </mat-card-content>
        
        <mat-card-actions>
          <button mat-button color="primary">
            <mat-icon>refresh</mat-icon>
            Actualizar
          </button>
          <button mat-button color="warn">
            <mat-icon>logout</mat-icon>
            Cerrar Sesión en Otros Dispositivos
          </button>
        </mat-card-actions>
      </mat-card>
    </div>
  `,
  styles: [`
    .security-info-container {
      max-width: 800px;
      margin: 20px auto;
      padding: 0 20px;
    }
    
    .security-section {
      margin-bottom: 24px;
    }
    
    .security-section h3 {
      color: #333;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .device-list, .activity-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    
    .device-item, .activity-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      background-color: #fafafa;
    }
    
    .device-info, .activity-info {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    
    .last-access, .activity-time {
      font-size: 0.85em;
      color: #666;
    }
    
    .activity-item .success {
      color: #4caf50;
    }
    
    .activity-item .warning {
      color: #ff9800;
    }
    
    .activity-item .error {
      color: #f44336;
    }
    
    mat-card-actions {
      display: flex;
      justify-content: space-between;
      padding: 16px;
    }
  `]
})
export class SecurityInfoComponent implements OnInit {
  lastLoginInfo = 'Cargando...';
  devices: any[] = [];
  recentActivity: any[] = [];

  constructor(private authService: AuthService) {}

  ngOnInit() {
    this.loadSecurityInfo();
  }

  loadSecurityInfo() {
    // TODO: Implementar llamadas reales a la API
    this.lastLoginInfo = 'Hace 2 horas desde Chrome en Windows';
    
    this.devices = [
      {
        name: 'Chrome en Windows',
        type: 'desktop',
        location: 'Bogotá, Colombia',
        lastAccess: new Date(),
        trusted: true
      },
      {
        name: 'Safari en iPhone',
        type: 'mobile',
        location: 'Medellín, Colombia',
        lastAccess: new Date(Date.now() - 86400000), // 1 día atrás
        trusted: false
      }
    ];
    
    this.recentActivity = [
      {
        type: 'success',
        description: 'Login exitoso desde Chrome en Windows',
        timestamp: new Date()
      },
      {
        type: 'warning',
        description: 'Nuevo dispositivo detectado: Safari en iPhone',
        timestamp: new Date(Date.now() - 86400000)
      },
      {
        type: 'error',
        description: 'Intento de login fallido desde IP desconocida',
        timestamp: new Date(Date.now() - 172800000) // 2 días atrás
      }
    ];
  }

  getDeviceIcon(type: string): string {
    switch (type) {
      case 'mobile': return 'smartphone';
      case 'tablet': return 'tablet';
      default: return 'computer';
    }
  }

  getActivityIcon(type: string): string {
    switch (type) {
      case 'success': return 'check_circle';
      case 'warning': return 'warning';
      case 'error': return 'error';
      default: return 'info';
    }
  }
}
