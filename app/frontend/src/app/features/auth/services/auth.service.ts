import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { environment } from '../../../../environments/environment';

export interface CurrentUser {
  id?: string;
  email: string;
}

export interface AuthResponse {
  token: string;
}

export interface RegisterPayload {
  email: string;
  password: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.apiUrl;
  private tokenKey = 'token';

  private currentUserSubject = new BehaviorSubject<CurrentUser | null>(null);
  currentUser$ = this.currentUserSubject.asObservable();

  constructor(private http: HttpClient) {}

  register(payload: RegisterPayload): Observable<unknown> {
    return this.http.post(`${this.apiUrl}/register`, payload);
  }

  login(email: string, password: string): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/auth`, {
      email,
      password
    }).pipe(
      tap((response) => {
        this.setToken(response.token);
      })
    );
  }

  getMe(): Observable<CurrentUser> {
    return this.http.get<CurrentUser>(`${this.apiUrl}/me`).pipe(
      tap((user) => {
        this.currentUserSubject.next(user);
      })
    );
  }

  loadCurrentUser(): void {
    if (!this.getToken()) {
      this.currentUserSubject.next(null);
      return;
    }

    this.getMe().subscribe({
      error: () => {
        this.logout();
      }
    });
  }

  setToken(token: string): void {
    localStorage.setItem(this.tokenKey, token);
  }

  getToken(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  isLoggedIn(): boolean {
    return !!this.getToken();
  }

  logout(): void {
    localStorage.removeItem(this.tokenKey);
    this.currentUserSubject.next(null);
  }
}