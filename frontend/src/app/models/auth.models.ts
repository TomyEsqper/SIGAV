export interface LoginRequest {
  tenant: string;
  usernameOrEmail: string;
  password: string;
}

export interface LoginResponse {
  accessToken: string;
  expiresIn: number;
  tokenType: string;
  user: UserInfo;
  tenant: string;
  isNewDevice?: boolean;
  deviceName?: string;
}

export interface UserInfo {
  id: string;
  name: string;
  email: string;
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
