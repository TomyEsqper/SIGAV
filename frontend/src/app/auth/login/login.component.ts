import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { MatSnackBar } from '@angular/material/snack-bar';
import { AuthService } from '../auth.service';
import { PasswordStrengthComponent } from '../../shared/components/password-strength/password-strength.component';
import { LoadingSpinnerComponent } from '../../shared/components/loading-spinner/loading-spinner.component';
import { SmoothTransitionComponent } from '../../shared/components/smooth-transition/smooth-transition.component';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    PasswordStrengthComponent,
    LoadingSpinnerComponent,
    SmoothTransitionComponent
  ],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss']
})
export class LoginComponent {
  loginForm: FormGroup;
  loading = false;
  showPassword = false;
  showPasswordStrength = false;
  formSubmitted = false;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private snackBar: MatSnackBar
  ) {
    this.loginForm = this.fb.group({
      tenant: ['', [Validators.required, Validators.minLength(3)]],
      usernameOrEmail: ['', [Validators.required, Validators.minLength(3)]],
      password: ['', [Validators.required, Validators.minLength(8)]]
    });
  }

  togglePassword() {
    this.showPassword = !this.showPassword;
  }

  onPasswordFocus() {
    this.showPasswordStrength = true;
  }

  onPasswordBlur() {
    // Mantener visible por un momento después de perder el foco
    setTimeout(() => {
      this.showPasswordStrength = false;
    }, 2000);
  }

  loginWithGoogle() {
    // TODO: Implement Google OAuth login
    this.snackBar.open('Funcionalidad de Google Login en desarrollo', 'Cerrar', {
      duration: 3000
    });
  }

  onSubmit() {
    this.formSubmitted = true;
    
    if (this.loginForm.valid) {
      this.loading = true;
      
      // Normalizar inputs
      const formValue = this.loginForm.value;
      const request = {
        tenant: formValue.tenant.trim().toLowerCase(),
        usernameOrEmail: formValue.usernameOrEmail.trim(),
        password: formValue.password
      };
      
      this.authService.login(request).subscribe({
        next: (response) => {
          this.loading = false;
          
          // Mostrar mensaje especial si es un dispositivo nuevo
          if (response.isNewDevice) {
            this.snackBar.open(
              `¡Bienvenido! Se ha detectado un nuevo dispositivo: ${response.deviceName || 'Dispositivo desconocido'}. 
              Se ha enviado una notificación de seguridad.`, 
              'Entendido', 
              { duration: 8000 }
            );
          } else {
            this.snackBar.open('Sesión iniciada correctamente', 'Cerrar', {
              duration: 3000
            });
          }
          
          this.router.navigate(['/busetas']);
        },
        error: (error) => {
          this.loading = false;
          let errorMessage = 'Error al iniciar sesión';
          
          if (error.status === 423) {
            errorMessage = 'Acceso bloqueado temporalmente. Intente más tarde.';
          } else if (error.error?.message) {
            errorMessage = error.error.message;
          }
          
          this.snackBar.open(errorMessage, 'Cerrar', { duration: 5000 });
        }
      });
    } else {
      // Mark all fields as touched to show validation errors
      Object.keys(this.loginForm.controls).forEach(key => {
        const control = this.loginForm.get(key);
        control?.markAsTouched();
      });
    }
  }
}
