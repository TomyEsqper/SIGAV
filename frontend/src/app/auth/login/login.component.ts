import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar } from '@angular/material/snack-bar';
import { AuthService } from '../auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule
  ],
  template: `
    <div class="login-container">
      <mat-card class="login-card">
        <mat-card-header>
          <mat-card-title>SIGAV - Sistema de Alistamiento</mat-card-title>
          <mat-card-subtitle>Iniciar Sesión</mat-card-subtitle>
        </mat-card-header>
        
        <mat-card-content>
          <form [formGroup]="loginForm" (ngSubmit)="onSubmit()">
            <mat-form-field appearance="outline" class="full-width">
              <mat-label>Email</mat-label>
              <input matInput formControlName="email" type="email" placeholder="admin@sigav.local">
              <mat-error *ngIf="loginForm.get('email')?.hasError('required')">
                El email es obligatorio
              </mat-error>
              <mat-error *ngIf="loginForm.get('email')?.hasError('email')">
                Formato de email inválido
              </mat-error>
            </mat-form-field>
            
            <mat-form-field appearance="outline" class="full-width">
              <mat-label>Contraseña</mat-label>
              <input matInput formControlName="password" type="password" placeholder="Admin_123!">
              <mat-error *ngIf="loginForm.get('password')?.hasError('required')">
                La contraseña es obligatoria
              </mat-error>
            </mat-form-field>
            
            <button 
              mat-raised-button 
              color="primary" 
              type="submit" 
              class="full-width"
              [disabled]="loginForm.invalid || loading">
              <mat-icon *ngIf="!loading">login</mat-icon>
              <span *ngIf="loading">Iniciando...</span>
              <span *ngIf="!loading">Iniciar Sesión</span>
            </button>
          </form>
        </mat-card-content>
        
        <mat-card-footer>
          <div class="demo-credentials">
            <h4>Credenciales de Demo:</h4>
            <p><strong>Admin:</strong> admin@sigav.local / Admin_123!</p>
            <p><strong>Inspector:</strong> inspector@sigav.local / Inspector_123!</p>
            <p><strong>Mecánico:</strong> mecanico@sigav.local / Mecanico_123!</p>
          </div>
        </mat-card-footer>
      </mat-card>
    </div>
  `,
  styles: [`
    .login-container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .login-card {
      max-width: 400px;
      width: 100%;
      margin: 20px;
    }
    
    .full-width {
      width: 100%;
      margin-bottom: 16px;
    }
    
    .demo-credentials {
      padding: 16px;
      background-color: #f5f5f5;
      border-radius: 4px;
      margin-top: 16px;
    }
    
    .demo-credentials h4 {
      margin: 0 0 8px 0;
      color: #666;
    }
    
    .demo-credentials p {
      margin: 4px 0;
      font-size: 12px;
      color: #666;
    }
  `]
})
export class LoginComponent {
  loginForm: FormGroup;
  loading = false;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private snackBar: MatSnackBar
  ) {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', Validators.required]
    });
  }

  onSubmit() {
    if (this.loginForm.valid) {
      this.loading = true;
      const { email, password } = this.loginForm.value;
      
      this.authService.login(email, password).subscribe({
        next: () => {
          this.router.navigate(['/busetas']);
          this.snackBar.open('Sesión iniciada correctamente', 'Cerrar', {
            duration: 3000
          });
        },
        error: (error) => {
          this.loading = false;
          this.snackBar.open(
            error.error?.message || 'Error al iniciar sesión', 
            'Cerrar', 
            { duration: 5000 }
          );
        }
      });
    }
  }
}
