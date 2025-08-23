export interface HistorialFiltrosRequest {
  busetaId?: number;
  from?: string;
  to?: string;
  page: number;
  pageSize: number;
}

export interface HistorialResponse {
  items: HistorialItem[];
  total: number;
  page: number;
  pageSize: number;
  totalPages: number;
}

export interface HistorialItem {
  id: number;
  busetaId: number;
  busetaPlaca: string;
  busetaModelo: string;
  plantillaNombre: string;
  inspectorNombre: string;
  fechaInicio: string;
  fechaCompletado?: string;
  completado: boolean;
  totalItems: number;
  itemsAprobados: number;
  itemsRechazados: number;
}
