import { Component, Input, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';

export interface PasswordStrength {
  score: number; // 0-4
  label: string;
  color: string;
  percentage: number;
}

@Component({
  selector: 'app-password-strength',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="password-strength" *ngIf="password && password.length > 0">
      <div class="strength-label">
        <span class="label-text">Fortaleza:</span>
        <span class="strength-text" [style.color]="strength.color">
          {{ strength.label }}
        </span>
      </div>
      
      <div class="strength-bar">
        <div class="strength-progress" 
             [style.width.%]="strength.percentage"
             [style.background]="strength.color"
             [@progressAnimation]="strength.percentage">
        </div>
      </div>
      
      <div class="strength-criteria" [@criteriaAnimation]="password.length > 0">
        <div class="criterion" 
             *ngFor="let criterion of criteria; trackBy: trackByCriterion"
             [class.met]="criterion.met"
             [@criterionAnimation]="criterion.met">
          <i class="criterion-icon" 
             [class]="criterion.met ? 'fas fa-check' : 'fas fa-times'"></i>
          <span class="criterion-text">{{ criterion.text }}</span>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .password-strength {
      margin-top: 8px;
      padding: 12px;
      background: #f8f9fa;
      border-radius: 8px;
      border: 1px solid #e9ecef;
      transition: all 0.3s ease;
    }

    .strength-label {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }

    .label-text {
      font-size: 14px;
      color: #6c757d;
      font-weight: 500;
    }

    .strength-text {
      font-size: 14px;
      font-weight: 600;
      transition: color 0.3s ease;
    }

    .strength-bar {
      height: 6px;
      background: #e9ecef;
      border-radius: 3px;
      overflow: hidden;
      margin-bottom: 12px;
    }

    .strength-progress {
      height: 100%;
      border-radius: 3px;
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
    }

    .strength-progress::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
      0% { transform: translateX(-100%); }
      100% { transform: translateX(100%); }
    }

    .strength-criteria {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .criterion {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: #6c757d;
      transition: all 0.3s ease;
      opacity: 0.7;
    }

    .criterion.met {
      color: #28a745;
      opacity: 1;
    }

    .criterion-icon {
      font-size: 10px;
      transition: all 0.3s ease;
    }

    .criterion.met .criterion-icon {
      color: #28a745;
    }

    .criterion:not(.met) .criterion-icon {
      color: #dc3545;
    }

    .criterion-text {
      font-weight: 500;
    }

    /* Animaciones */
    .password-strength {
      animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  `],
  animations: [
    {
      name: 'progressAnimation',
      trigger: 'progressAnimation',
      state: '0', style: { width: '0%' },
      state: '25', style: { width: '25%' },
      state: '50', style: { width: '50%' },
      state: '75', style: { width: '75%' },
      state: '100', style: { width: '100%' },
      transition: 'width 0.5s cubic-bezier(0.4, 0, 0.2, 1)'
    },
    {
      name: 'criteriaAnimation',
      trigger: 'criteriaAnimation',
      state: 'false', style: { opacity: 0, transform: 'translateX(-10px)' },
      state: 'true', style: { opacity: 1, transform: 'translateX(0)' },
      transition: 'all 0.3s ease'
    },
    {
      name: 'criterionAnimation',
      trigger: 'criterionAnimation',
      state: 'false', style: { opacity: 0.7, transform: 'scale(0.95)' },
      state: 'true', style: { opacity: 1, transform: 'scale(1)' },
      transition: 'all 0.3s ease'
    }
  ]
})
export class PasswordStrengthComponent implements OnChanges {
  @Input() password: string = '';

  strength: PasswordStrength = {
    score: 0,
    label: 'Muy débil',
    color: '#dc3545',
    percentage: 0
  };

  criteria = [
    { text: 'Al menos 8 caracteres', met: false, regex: /.{8,}/ },
    { text: 'Al menos una letra mayúscula', met: false, regex: /[A-Z]/ },
    { text: 'Al menos una letra minúscula', met: false, regex: /[a-z]/ },
    { text: 'Al menos un número', met: false, regex: /\d/ },
    { text: 'Al menos un carácter especial', met: false, regex: /[!@#$%^&*(),.?":{}|<>]/ }
  ];

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['password']) {
      this.calculateStrength();
    }
  }

  private calculateStrength(): void {
    if (!this.password) {
      this.strength = { score: 0, label: 'Muy débil', color: '#dc3545', percentage: 0 };
      this.updateCriteria();
      return;
    }

    let score = 0;
    
    // Verificar criterios
    this.criteria.forEach(criterion => {
      criterion.met = criterion.regex.test(this.password);
      if (criterion.met) score++;
    });

    // Calcular fortaleza basada en longitud y complejidad
    if (this.password.length >= 12) score++;
    if (this.password.length >= 16) score++;
    
    // Determinar nivel de fortaleza
    let label: string;
    let color: string;
    let percentage: number;

    if (score <= 1) {
      label = 'Muy débil';
      color = '#dc3545';
      percentage = 20;
    } else if (score <= 2) {
      label = 'Débil';
      color = '#fd7e14';
      percentage = 40;
    } else if (score <= 3) {
      label = 'Moderada';
      color = '#ffc107';
      percentage = 60;
    } else if (score <= 4) {
      label = 'Fuerte';
      color = '#28a745';
      percentage = 80;
    } else {
      label = 'Muy fuerte';
      color = '#20c997';
      percentage = 100;
    }

    this.strength = { score, label, color, percentage };
    this.updateCriteria();
  }

  private updateCriteria(): void {
    this.criteria.forEach(criterion => {
      criterion.met = criterion.regex.test(this.password);
    });
  }

  trackByCriterion(index: number, criterion: any): string {
    return criterion.text;
  }
}
