import { AsyncPipe, NgIf } from '@angular/common';
import { Component } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { map, Observable } from 'rxjs';
import { AuthService, CurrentUser } from '../../../features/auth/services/auth.service';

@Component({
  selector: 'app-header',
  standalone: true,
  imports: [RouterLink, NgIf, AsyncPipe],
  templateUrl: './header.component.html',
  styleUrl: './header.component.scss'
})
export class HeaderComponent {
  currentUser$!: Observable<CurrentUser | null>;
  initials$!: Observable<string>;

  constructor(
    private authService: AuthService,
    private router: Router
  ) {
    this.currentUser$ = this.authService.currentUser$;
    this.initials$ = this.currentUser$.pipe(
      map(user => user?.email ? user.email.substring(0, 2).toUpperCase() : '')
    );
  }

  logout(): void {
    this.authService.logout();
    this.router.navigate(['/login']);
  }
}