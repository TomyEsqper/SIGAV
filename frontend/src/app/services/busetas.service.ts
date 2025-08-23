import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { 
  Buseta, 
  BusetaListResponse, 
  CreateBusetaRequest, 
  UpdateBusetaRequest, 
  UpdateEstadoBusetaRequest,
  EstadoBuseta 
} from '../models/buseta.models';

@Injectable({
  providedIn: 'root'
})
export class BusetasService {
  constructor(private http: HttpClient) { }

  getBusetas(
    estado?: EstadoBuseta,
    q?: string,
    page: number = 1,
    pageSize: number = 20
  ): Observable<BusetaListResponse> {
    let params = new HttpParams()
      .set('page', page.toString())
      .set('pageSize', pageSize.toString());

    if (estado) {
      params = params.set('estado', estado);
    }

    if (q) {
      params = params.set('q', q);
    }

    return this.http.get<BusetaListResponse>(`${environment.apiBaseUrl}/busetas`, { params });
  }

  getBuseta(id: number): Observable<Buseta> {
    return this.http.get<Buseta>(`${environment.apiBaseUrl}/busetas/${id}`);
  }

  createBuseta(request: CreateBusetaRequest): Observable<Buseta> {
    return this.http.post<Buseta>(`${environment.apiBaseUrl}/busetas`, request);
  }

  updateBuseta(id: number, request: UpdateBusetaRequest): Observable<Buseta> {
    return this.http.put<Buseta>(`${environment.apiBaseUrl}/busetas/${id}`, request);
  }

  updateEstado(id: number, request: UpdateEstadoBusetaRequest): Observable<Buseta> {
    return this.http.patch<Buseta>(`${environment.apiBaseUrl}/busetas/${id}/estado`, request);
  }
}
