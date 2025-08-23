export interface ChecklistPlantilla {
  id: number;
  nombre: string;
  descripcion?: string;
  fechaCreacion: string;
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

export interface CreateChecklistEjecucionRequest {
  busetaId: number;
  checklistPlantillaId: number;
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

export interface ChecklistEjecucion {
  id: number;
  busetaId: number;
  busetaPlaca: string;
  checklistPlantillaId: number;
  plantillaNombre: string;
  inspectorId: string;
  inspectorNombre: string;
  fechaInicio: string;
  fechaCompletado?: string;
  observacionesGenerales?: string;
  completado: boolean;
  resultados: ChecklistItemResultado[];
}

export interface ChecklistItemResultado {
  id: number;
  itemPlantillaId: number;
  itemNombre: string;
  itemDescripcion?: string;
  itemOrden: number;
  aprobado: boolean;
  observacion?: string;
  fechaRegistro: string;
}
