import { Routes } from '@angular/router';
import { authGuard } from './auth/auth.guard';
import { roleGuard } from './auth/role.guard';

export const routes: Routes = [
  { path: '', redirectTo: '/login', pathMatch: 'full' },
  { path: 'login', component: () => import('./auth/login/login.component').then(m => m.LoginComponent) },
  
  {
    path: 'busetas',
    canActivate: [authGuard],
    children: [
      { path: '', component: () => import('./busetas/busetas.component').then(m => m.BusetasComponent) },
      { path: 'nueva', component: () => import('./busetas/buseta-form/buseta-form.component').then(m => m.BusetaFormComponent), canActivate: [() => roleGuard(['Admin'])] },
      { path: ':id/editar', component: () => import('./busetas/buseta-form/buseta-form.component').then(m => m.BusetaFormComponent), canActivate: [() => roleGuard(['Admin'])] }
    ]
  },
  
  {
    path: 'checklists',
    canActivate: [authGuard],
    children: [
      { path: 'plantillas', component: () => import('./checklists/plantillas/plantillas.component').then(m => m.PlantillasComponent), canActivate: [() => roleGuard(['Admin'])] },
      { path: 'ejecucion/nueva', component: () => import('./checklists/ejecucion/ejecucion.component').then(m => m.EjecucionComponent), canActivate: [() => roleGuard(['Admin', 'Inspector'])] }
    ]
  },
  
  {
    path: 'historial',
    canActivate: [authGuard],
    component: () => import('./historial/historial.component').then(m => m.HistorialComponent)
  },
  
  { path: '**', redirectTo: '/login' }
];
