export enum EstadoBuseta {
  Disponible = 'Disponible',
  EnMantenimiento = 'EnMantenimiento',
  EnRuta = 'EnRuta'
}

export interface Buseta {
  id: number;
  placa: string;
  modelo: string;
  capacidad: number;
  agencia: string;
  estado: EstadoBuseta;
  fechaCreacion: string;
  fechaActualizacion?: string;
}

export interface CreateBusetaRequest {
  placa: string;
  modelo: string;
  capacidad: number;
  agencia: string;
}

export interface UpdateBusetaRequest {
  modelo: string;
  capacidad: number;
  agencia: string;
}

export interface UpdateEstadoBusetaRequest {
  estado: EstadoBuseta;
}

export interface BusetaListResponse {
  items: Buseta[];
  total: number;
  page: number;
  pageSize: number;
  totalPages: number;
}
