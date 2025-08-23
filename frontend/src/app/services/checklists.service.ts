import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { 
  ChecklistPlantilla,
  CreateChecklistPlantillaRequest,
  UpdateChecklistPlantillaRequest,
  CreateChecklistEjecucionRequest,
  CompletarChecklistRequest,
  ChecklistEjecucion
} from '../models/checklist.models';

@Injectable({
  providedIn: 'root'
})
export class ChecklistsService {
  constructor(private http: HttpClient) { }

  // Plantillas
  getPlantillas(): Observable<ChecklistPlantilla[]> {
    return this.http.get<ChecklistPlantilla[]>(`${environment.apiBaseUrl}/checklists/plantillas`);
  }

  getPlantilla(id: number): Observable<ChecklistPlantilla> {
    return this.http.get<ChecklistPlantilla>(`${environment.apiBaseUrl}/checklists/plantillas/${id}`);
  }

  createPlantilla(request: CreateChecklistPlantillaRequest): Observable<ChecklistPlantilla> {
    return this.http.post<ChecklistPlantilla>(`${environment.apiBaseUrl}/checklists/plantillas`, request);
  }

  updatePlantilla(id: number, request: UpdateChecklistPlantillaRequest): Observable<ChecklistPlantilla> {
    return this.http.put<ChecklistPlantilla>(`${environment.apiBaseUrl}/checklists/plantillas/${id}`, request);
  }

  // Ejecuciones
  createEjecucion(request: CreateChecklistEjecucionRequest): Observable<ChecklistEjecucion> {
    return this.http.post<ChecklistEjecucion>(`${environment.apiBaseUrl}/checklists/ejecuciones`, request);
  }

  completarEjecucion(id: number, request: CompletarChecklistRequest): Observable<ChecklistEjecucion> {
    return this.http.post<ChecklistEjecucion>(`${environment.apiBaseUrl}/checklists/ejecuciones/${id}/completar`, request);
  }

  getEjecuciones(
    busetaId?: number,
    from?: string,
    to?: string
  ): Observable<ChecklistEjecucion[]> {
    let params = new HttpParams();

    if (busetaId) {
      params = params.set('busetaId', busetaId.toString());
    }

    if (from) {
      params = params.set('from', from);
    }

    if (to) {
      params = params.set('to', to);
    }

    return this.http.get<ChecklistEjecucion[]>(`${environment.apiBaseUrl}/checklists/ejecuciones`, { params });
  }

  getEjecucion(id: number): Observable<ChecklistEjecucion> {
    return this.http.get<ChecklistEjecucion>(`${environment.apiBaseUrl}/checklists/ejecuciones/${id}`);
  }
}
