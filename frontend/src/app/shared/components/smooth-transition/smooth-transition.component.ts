import { Component, Input, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { trigger, state, style, transition, animate } from '@angular/animations';

@Component({
  selector: 'app-smooth-transition',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="transition-wrapper" 
         [@transitionState]="state"
         [class]="transitionClass"
         (transitionend)="onTransitionEnd()">
      <ng-content></ng-content>
    </div>
  `,
  styles: [`
    .transition-wrapper {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .transition-wrapper.fade {
      opacity: 1;
    }

    .transition-wrapper.fade.fade-out {
      opacity: 0;
    }

    .transition-wrapper.slide {
      transform: translateY(0);
      opacity: 1;
    }

    .transition-wrapper.slide.slide-out {
      transform: translateY(-20px);
      opacity: 0;
    }

    .transition-wrapper.scale {
      transform: scale(1);
      opacity: 1;
    }

    .transition-wrapper.scale.scale-out {
      transform: scale(0.95);
      opacity: 0;
    }

    .transition-wrapper.slide-up {
      transform: translateY(0);
      opacity: 1;
    }

    .transition-wrapper.slide-up.slide-up-out {
      transform: translateY(20px);
      opacity: 0;
    }

    /* Efectos especiales */
    .transition-wrapper.glow {
      box-shadow: 0 0 20px rgba(0, 123, 255, 0.3);
    }

    .transition-wrapper.bounce {
      animation: bounce 0.6s ease-out;
    }

    @keyframes bounce {
      0%, 20%, 53%, 80%, 100% {
        transform: translateY(0);
      }
      40%, 43% {
        transform: translateY(-10px);
      }
      70% {
        transform: translateY(-5px);
      }
      90% {
        transform: translateY(-2px);
      }
    }

    .transition-wrapper.shake {
      animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
  `],
  animations: [
    trigger('transitionState', [
      state('in', style({
        opacity: 1,
        transform: 'translateY(0) scale(1)'
      })),
      state('out', style({
        opacity: 0,
        transform: 'translateY(-20px) scale(0.95)'
      })),
      transition('in => out', [
        animate('300ms cubic-bezier(0.4, 0, 0.2, 1)')
      ]),
      transition('out => in', [
        animate('300ms cubic-bezier(0.4, 0, 0.2, 1)')
      ])
    ])
  ]
})
export class SmoothTransitionComponent implements OnInit, OnDestroy {
  @Input() type: 'fade' | 'slide' | 'scale' | 'slide-up' = 'fade';
  @Input() duration: number = 300;
  @Input() delay: number = 0;
  @Input() effect: 'none' | 'glow' | 'bounce' | 'shake' = 'none';

  state: 'in' | 'out' = 'out';
  transitionClass: string = '';

  private timeoutId?: number;

  ngOnInit(): void {
    this.transitionClass = this.type;
    
    if (this.delay > 0) {
      this.timeoutId = window.setTimeout(() => {
        this.state = 'in';
      }, this.delay);
    } else {
      this.state = 'in';
    }

    // Aplicar efectos especiales
    if (this.effect !== 'none') {
      setTimeout(() => {
        this.transitionClass += ` ${this.effect}`;
      }, 100);
    }
  }

  ngOnDestroy(): void {
    if (this.timeoutId) {
      clearTimeout(this.timeoutId);
    }
  }

  onTransitionEnd(): void {
    // Remover clases de efectos después de la animación
    if (this.effect !== 'none') {
      setTimeout(() => {
        this.transitionClass = this.transitionClass.replace(` ${this.effect}`, '');
      }, 1000);
    }
  }

  // Método para activar transición de salida
  exit(): Promise<void> {
    return new Promise((resolve) => {
      this.state = 'out';
      setTimeout(() => {
        resolve();
      }, this.duration);
    });
  }

  // Método para activar transición de entrada
  enter(): void {
    this.state = 'in';
  }
}
