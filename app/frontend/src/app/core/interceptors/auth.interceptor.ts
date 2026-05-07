import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { AuthService } from '../../features/auth/services/auth.service';

// Intercepteur HTTP : ajoute le token JWT a chaque requete et gere l'expiration (401 -> logout + redirect).
export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);
  const auth = inject(AuthService);

  const token = localStorage.getItem('token');

  // On ajoute l'entete Authorization seulement si on a un token (sinon on laisse passer tel quel pour /login, /register, /docs).
  const authReq = token
    ? req.clone({ setHeaders: { Authorization: `Bearer ${token}` } })
    : req;

  return next(authReq).pipe(
    catchError((error: HttpErrorResponse) => {
      // Si le token est invalide/expire, on degage l'user et on renvoie vers la page de login.
      // On exclut /api/login_check pour ne pas faire de boucle quand l'user se trompe de password.
      if (error.status === 401 && !req.url.includes('/api/login_check')) {
        auth.logout();
        router.navigate(['/login']);
      }

      return throwError(() => error);
    })
  );
};