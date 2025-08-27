import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { MatTableModule } from '@angular/material/table';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatPaginatorModule, PageEvent } from '@angular/material/paginator';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { MatMenuModule } from '@angular/material/menu';
import { MatSnackBar } from '@angular/material/snack-bar';
import { FormsModule } from '@angular/forms';
import { Buseta, BusetaListResponse } from '../models';
import { BusetasService } from './busetas.service';
import { AuthService } from '../auth/auth.service';

@Component({
  selector: 'app-busetas',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    MatTableModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatPaginatorModule,
    MatCardModule,
    MatChipsModule,
    MatMenuModule
  ],
  template: `
    <mat-card>
      <mat-card-header>
        <mat-card-title>Gestión de Busetas</mat-card-title>
        <mat-card-subtitle>Administración del parque automotor</mat-card-subtitle>
      </mat-card-header>
      
      <mat-card-content>
        <!-- Filtros -->
        <div class="filters-container">
          <mat-form-field appearance="outline">
            <mat-label>Buscar</mat-label>
            <input matInput [(ngModel)]="filtros.q" placeholder="Placa, modelo o agencia">
            <mat-icon matSuffix>search</mat-icon>
          </mat-form-field>
          
          <mat-form-field appearance="outline">
            <mat-label>Estado</mat-label>
            <mat-select [(ngModel)]="filtros.estado">
              <mat-option value="">Todos</mat-option>
              <mat-option value="Disponible">Disponible</mat-option>
              <mat-option value="EnMantenimiento">En Mantenimiento</mat-option>
              <mat-option value="EnRuta">En Ruta</mat-option>
            </mat-select>
          </mat-form-field>
          
          <button mat-raised-button color="primary" (click)="aplicarFiltros()">
            <mat-icon>filter_list</mat-icon>
            Filtrar
          </button>
          
          <button mat-raised-button color="accent" (click)="limpiarFiltros()">
            <mat-icon>clear</mat-icon>
            Limpiar
          </button>
        </div>

        <!-- Botón Nueva Buseta -->
        <div class="actions-container" *ngIf="authService.hasRole('Admin')">
          <button mat-raised-button color="primary" routerLink="/busetas/nueva">
            <mat-icon>add</mat-icon>
            Nueva Buseta
          </button>
        </div>

        <!-- Tabla -->
        <div class="table-container">
          <table mat-table [dataSource]="busetas" class="mat-elevation-z8">
            <ng-container matColumnDef="placa">
              <th mat-header-cell *matHeaderCellDef>Placa</th>
              <td mat-cell *matCellDef="let buseta">{{ buseta.placa }}</td>
            </ng-container>

            <ng-container matColumnDef="modelo">
              <th mat-header-cell *matHeaderCellDef>Modelo</th>
              <td mat-cell *matCellDef="let buseta">{{ buseta.modelo }}</td>
            </ng-container>

            <ng-container matColumnDef="capacidad">
              <th mat-header-cell *matHeaderCellDef>Capacidad</th>
              <td mat-cell *matCellDef="let buseta">{{ buseta.capacidad }} pax</td>
            </ng-container>

            <ng-container matColumnDef="agencia">
              <th mat-header-cell *matHeaderCellDef>Agencia</th>
              <td mat-cell *matCellDef="let buseta">{{ buseta.agencia }}</td>
            </ng-container>

            <ng-container matColumnDef="estado">
              <th mat-header-cell *matHeaderCellDef>Estado</th>
              <td mat-cell *matCellDef="let buseta">
                <mat-chip [color]="getEstadoColor(buseta.estado)" selected>
                  {{ getEstadoLabel(buseta.estado) }}
                </mat-chip>
              </td>
            </ng-container>

            <ng-container matColumnDef="acciones">
              <th mat-header-cell *matHeaderCellDef>Acciones</th>
              <td mat-cell *matCellDef="let buseta">
                <button mat-icon-button color="primary" [matMenuTriggerFor]="menu">
                  <mat-icon>more_vert</mat-icon>
                </button>
                <mat-menu #menu="matMenu">
                  <button mat-menu-item (click)="verBuseta(buseta)">
                    <mat-icon>visibility</mat-icon>
                    Ver
                  </button>
                  <button mat-menu-item (click)="editarBuseta(buseta)" *ngIf="authService.hasRole('Admin')">
                    <mat-icon>edit</mat-icon>
                    Editar
                  </button>
                  <button mat-menu-item (click)="cambiarEstado(buseta)" *ngIf="authService.hasRole('Admin')">
                    <mat-icon>swap_horiz</mat-icon>
                    Cambiar Estado
                  </button>
                </mat-menu>
              </td>
            </ng-container>

            <tr mat-header-row *matHeaderRowDef="columnas"></tr>
            <tr mat-row *matRowDef="let row; columns: columnas;"></tr>
          </table>

          <mat-paginator 
            [length]="total"
            [pageSize]="filtros.pageSize"
            [pageIndex]="filtros.page - 1"
            [pageSizeOptions]="[10, 20, 50]"
            (page)="onPageChange($event)">
          </mat-paginator>
        </div>
      </mat-card-content>
    </mat-card>
  `,
  styles: [`
    .filters-container {
      display: flex;
      gap: 16px;
      margin-bottom: 20px;
      align-items: center;
      flex-wrap: wrap;
    }
    
    .filters-container mat-form-field {
      min-width: 200px;
    }
    
    .actions-container {
      margin-bottom: 20px;
    }
    
    .table-container {
      margin-top: 20px;
    }
    
    table {
      width: 100%;
    }
    
    .mat-column-acciones {
      width: 80px;
      text-align: center;
    }
    
    .mat-column-capacidad {
      width: 100px;
      text-align: center;
    }
    
    .mat-column-estado {
      width: 150px;
    }
  `]
})
export class BusetasComponent implements OnInit {
  busetas: Buseta[] = [];
  total = 0;
  columnas = ['placa', 'modelo', 'capacidad', 'agencia', 'estado', 'acciones'];
  
  filtros = {
    q: '',
    estado: '',
    page: 1,
    pageSize: 20
  };

  constructor(
    private busetasService: BusetasService,
    public authService: AuthService,
    private router: Router,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit() {
    this.cargarBusetas();
  }

  cargarBusetas() {
    this.busetasService.getBusetas(this.filtros).subscribe({
      next: (response: BusetaListResponse) => {
        this.busetas = response.items;
        this.total = response.total;
      },
      error: (error) => {
        this.snackBar.open('Error al cargar busetas', 'Cerrar', { duration: 3000 });
      }
    });
  }

  aplicarFiltros() {
    this.filtros.page = 1;
    this.cargarBusetas();
  }

  limpiarFiltros() {
    this.filtros = {
      q: '',
      estado: '',
      page: 1,
      pageSize: 20
    };
    this.cargarBusetas();
  }

  onPageChange(event: PageEvent) {
    this.filtros.page = event.pageIndex + 1;
    this.filtros.pageSize = event.pageSize;
    this.cargarBusetas();
  }

  verBuseta(buseta: Buseta) {
    // Implementar vista detallada si es necesario
    this.snackBar.open(`Viendo buseta ${buseta.placa}`, 'Cerrar', { duration: 2000 });
  }

  editarBuseta(buseta: Buseta) {
    this.router.navigate(['/busetas', buseta.id, 'editar']);
  }

  cambiarEstado(buseta: Buseta) {
    // Implementar cambio de estado
    this.snackBar.open(`Cambiando estado de ${buseta.placa}`, 'Cerrar', { duration: 2000 });
  }

  getEstadoColor(estado: string): string {
    switch (estado) {
      case 'Disponible': return 'primary';
      case 'EnMantenimiento': return 'warn';
      case 'EnRuta': return 'accent';
      default: return 'primary';
    }
  }

  getEstadoLabel(estado: string): string {
    switch (estado) {
      case 'Disponible': return 'Disponible';
      case 'EnMantenimiento': return 'En Mantenimiento';
      case 'EnRuta': return 'En Ruta';
      default: return estado;
    }
  }
}
