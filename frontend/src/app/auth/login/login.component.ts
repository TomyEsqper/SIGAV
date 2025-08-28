import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { MatSnackBar } from '@angular/material/snack-bar';
import { AuthService } from '../auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule
  ],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss']
})
export class LoginComponent {
  loginForm: FormGroup;
  loading = false;
  showPassword = false;

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

  loginWithGoogle() {
    // TODO: Implement Google OAuth login
    this.snackBar.open('Funcionalidad de Google Login en desarrollo', 'Cerrar', {
      duration: 3000
    });
  }

  onSubmit() {
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
          this.router.navigate(['/busetas']);
          this.snackBar.open('Sesión iniciada correctamente', 'Cerrar', {
            duration: 3000
          });
        },
        error: (error) => {
          this.loading = false;
          let errorMessage = 'Error al iniciar sesión';
          
          if (error.status === 423) {
            errorMessage = 'Cuenta bloqueada temporalmente';
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
