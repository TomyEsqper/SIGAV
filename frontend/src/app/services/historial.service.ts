import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { HistorialResponse, HistorialFiltrosRequest } from '../models/historial.models';

@Injectable({
  providedIn: 'root'
})
export class HistorialService {
  constructor(private http: HttpClient) { }

  getHistorial(
    busetaId?: number,
    from?: string,
    to?: string,
    page: number = 1,
    pageSize: number = 20
  ): Observable<HistorialResponse> {
    let params = new HttpParams()
      .set('page', page.toString())
      .set('pageSize', pageSize.toString());

    if (busetaId) {
      params = params.set('busetaId', busetaId.toString());
    }

    if (from) {
      params = params.set('from', from);
    }

    if (to) {
      params = params.set('to', to);
    }

    return this.http.get<HistorialResponse>(`${environment.apiBaseUrl}/historial`, { params });
  }

  exportCsv(
    busetaId?: number,
    from?: string,
    to?: string
  ): Observable<Blob> {
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

    return this.http.get(`${environment.apiBaseUrl}/historial/export/csv`, { 
      params, 
      responseType: 'blob' 
    });
  }

  exportPdf(
    busetaId?: number,
    from?: string,
    to?: string
  ): Observable<Blob> {
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

    return this.http.get(`${environment.apiBaseUrl}/historial/export/pdf`, { 
      params, 
      responseType: 'blob' 
    });
  }
}
