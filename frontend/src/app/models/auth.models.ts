export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  accessToken: string;
  tokenType: string;
  expiresAt: string;
  usuario: UsuarioInfo;
}

export interface UsuarioInfo {
  id: string;
  email: string;
  nombre: string;
  apellido: string;
  rol: string;
}

export enum RolUsuario {
  Admin = 'Admin',
  Inspector = 'Inspector',
  Mecanico = 'Mecanico'
}
