import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { Buseta, BusetaListResponse, CreateBusetaRequest, UpdateBusetaRequest, UpdateEstadoRequest } from '../models';
import { ApiService } from '../services/api.service';

@Injectable({
  providedIn: 'root'
})
export class BusetasService {
  constructor(private apiService: ApiService) {}

  getBusetas(filtros: any): Observable<BusetaListResponse> {
    return this.apiService.get<BusetaListResponse>('/busetas', filtros);
  }

  getBuseta(id: number): Observable<Buseta> {
    return this.apiService.get<Buseta>(`/busetas/${id}`);
  }

  createBuseta(request: CreateBusetaRequest): Observable<Buseta> {
    return this.apiService.post<Buseta>('/busetas', request);
  }

  updateBuseta(id: number, request: UpdateBusetaRequest): Observable<Buseta> {
    return this.apiService.put<Buseta>(`/busetas/${id}`, request);
  }

  updateEstado(id: number, request: UpdateEstadoRequest): Observable<Buseta> {
    return this.apiService.patch<Buseta>(`/busetas/${id}/estado`, request);
  }
}
