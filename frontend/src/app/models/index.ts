export interface Usuario {
  id: string;
  email: string;
  nombre: string;
  apellido: string;
  rol: string;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  accessToken: string;
  tokenType: string;
  expiresAt: string;
  usuario: Usuario;
}

export interface Buseta {
  id: number;
  placa: string;
  modelo: string;
  capacidad: number;
  agencia: string;
  estado: string;
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

export interface UpdateEstadoRequest {
  estado: string;
}

export interface BusetaListResponse {
  items: Buseta[];
  total: number;
  page: number;
  pageSize: number;
  totalPages: number;
}

export interface ChecklistPlantilla {
  id: number;
  nombre: string;
  descripcion?: string;
  fechaCreacion: string;
  fechaActualizacion?: string;
  activa: boolean;
  items: ChecklistItem[];
}

export interface ChecklistItem {
  id: number;
  nombre: string;
  descripcion?: string;
  orden: number;
  requiereObservacion: boolean;
}

export interface CreateChecklistPlantillaRequest {
  nombre: string;
  descripcion?: string;
  items: CreateChecklistItemRequest[];
}

export interface CreateChecklistItemRequest {
  nombre: string;
  descripcion?: string;
  orden: number;
  requiereObservacion: boolean;
}

export interface UpdateChecklistPlantillaRequest {
  nombre: string;
  descripcion?: string;
  items: UpdateChecklistItemRequest[];
}

export interface UpdateChecklistItemRequest {
  id: number;
  nombre: string;
  descripcion?: string;
  orden: number;
  requiereObservacion: boolean;
}

export interface IniciarChecklistRequest {
  busetaId: number;
  plantillaId: number;
}

export interface ChecklistEjecucion {
  id: number;
  busetaId: number;
  placaBuseta: string;
  plantillaId: number;
  nombrePlantilla: string;
  inspectorId: string;
  nombreInspector: string;
  fechaInicio: string;
  fechaCompletado?: string;
  observacionesGenerales?: string;
  estado: string;
  resultados: ChecklistItemResultado[];
}

export interface ChecklistItemResultado {
  id: number;
  itemPlantillaId: number;
  nombreItem: string;
  descripcionItem?: string;
  aprobado: boolean;
  observacion?: string;
  fechaVerificacion: string;
}

export interface CompletarChecklistRequest {
  resultados: ChecklistItemResultadoRequest[];
  observacionesGenerales?: string;
}

export interface ChecklistItemResultadoRequest {
  itemPlantillaId: number;
  aprobado: boolean;
  observacion?: string;
}

export interface HistorialFiltros {
  busetaId?: number;
  from?: Date;
  to?: Date;
  page: number;
  pageSize: number;
}

export interface HistorialResponse {
  items: ChecklistEjecucion[];
  total: number;
  page: number;
  pageSize: number;
  totalPages: number;
}
