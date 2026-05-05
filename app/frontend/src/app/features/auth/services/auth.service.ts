import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { environment } from '../../../../environments/environment';

export interface CurrentUser {
  id?: string;
  email: string;
}

interface AuthResponse {
  token: string;
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

  register(email: string, password: string): Observable<unknown> {
    return this.http.post(`${this.apiUrl}/api/register`, {
      email,
      password
    });
  }

 login(email: string, password: string): Observable<AuthResponse> {
  return this.http.post<AuthResponse>(`${this.apiUrl}/api/login_check`, {
    email,
    password
  }).pipe(
    tap((res) => {
      localStorage.setItem(this.tokenKey, res.token);
    })
  );
}

  getMe(): Observable<CurrentUser> {
    return this.http.get<CurrentUser>(`${this.apiUrl}/api/me`).pipe(
      tap((user) => this.currentUserSubject.next(user))
    );
  }

  loadCurrentUser(): void {
    const token = this.getToken();

    if (!token) {
      this.currentUserSubject.next(null);
      return;
    }

    this.getMe().subscribe({
      error: () => this.logout()
    });
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