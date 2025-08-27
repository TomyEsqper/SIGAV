import { Routes } from '@angular/router';
import { authGuard } from './auth/auth.guard';
import { roleGuard } from './auth/role.guard';
import { LoginComponent } from './auth/login/login.component';
import { BusetasComponent } from './busetas/busetas.component';
import { ProfileComponent } from './profile/profile.component';

export const routes: Routes = [
  { path: '', redirectTo: '/login', pathMatch: 'full' },
  { path: 'login', component: LoginComponent },
  
  {
    path: 'busetas',
    canActivate: [authGuard],
    children: [
      { path: '', component: BusetasComponent },
      { path: 'nueva', component: BusetasComponent, canActivate: [() => roleGuard(['Admin'])] },
      { path: ':id/editar', component: BusetasComponent, canActivate: [() => roleGuard(['Admin'])] }
    ]
  },
  
  {
    path: 'checklists',
    canActivate: [authGuard],
    children: [
      { path: 'plantillas', component: ProfileComponent, canActivate: [() => roleGuard(['Admin'])] },
      { path: 'ejecucion/nueva', component: ProfileComponent, canActivate: [() => roleGuard(['Admin', 'Inspector'])] }
    ]
  },
  
  {
    path: 'historial',
    canActivate: [authGuard],
    component: ProfileComponent
  },
  
  { path: '**', redirectTo: '/login' }
];
