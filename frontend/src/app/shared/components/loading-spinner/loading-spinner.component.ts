import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-loading-spinner',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="loading-container" [class.overlay]="overlay" [class.small]="size === 'small'">
      <div class="spinner-wrapper">
        <div class="spinner" [class.small]="size === 'small'">
          <div class="spinner-ring"></div>
          <div class="spinner-ring"></div>
          <div class="spinner-ring"></div>
        </div>
        <div class="loading-text" *ngIf="message">{{ message }}</div>
      </div>
    </div>
  `,
  styles: [`
    .loading-container {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .loading-container.overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(4px);
      z-index: 9999;
      padding: 0;
    }

    .spinner-wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 16px;
    }

    .spinner {
      position: relative;
      width: 40px;
      height: 40px;
    }

    .spinner.small {
      width: 24px;
      height: 24px;
    }

    .spinner-ring {
      position: absolute;
      width: 100%;
      height: 100%;
      border: 3px solid transparent;
      border-top: 3px solid #007bff;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    .spinner-ring:nth-child(1) {
      animation-delay: 0s;
    }

    .spinner-ring:nth-child(2) {
      width: 70%;
      height: 70%;
      top: 15%;
      left: 15%;
      border-top-color: #28a745;
      animation-delay: 0.2s;
    }

    .spinner-ring:nth-child(3) {
      width: 40%;
      height: 40%;
      top: 30%;
      left: 30%;
      border-top-color: #ffc107;
      animation-delay: 0.4s;
    }

    .spinner.small .spinner-ring {
      border-width: 2px;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .loading-text {
      font-size: 14px;
      color: #6c757d;
      font-weight: 500;
      text-align: center;
      animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }

    /* Animaciones de entrada */
    .loading-container {
      animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: scale(0.95);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    /* Efecto de ondas */
    .spinner::after {
      content: '';
      position: absolute;
      top: -10px;
      left: -10px;
      right: -10px;
      bottom: -10px;
      border: 2px solid rgba(0, 123, 255, 0.1);
      border-radius: 50%;
      animation: ripple 2s ease-out infinite;
    }

    @keyframes ripple {
      0% {
        transform: scale(0.8);
        opacity: 1;
      }
      100% {
        transform: scale(1.2);
        opacity: 0;
      }
    }
  `]
})
export class LoadingSpinnerComponent {
  @Input() message: string = '';
  @Input() overlay: boolean = false;
  @Input() size: 'normal' | 'small' = 'normal';
}
