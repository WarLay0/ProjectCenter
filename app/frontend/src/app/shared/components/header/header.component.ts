import { AsyncPipe, NgIf } from '@angular/common';
import { Component, ElementRef, HostListener } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { LucideAngularModule } from 'lucide-angular';
import { map, Observable } from 'rxjs';
import { AuthService, CurrentUser } from '../../../features/auth/services/auth.service';

@Component({
  selector: 'app-header',
  standalone: true,
  imports: [RouterLink, NgIf, AsyncPipe, LucideAngularModule],
  templateUrl: './header.component.html',
  styleUrl: './header.component.scss'
})
export class HeaderComponent {
  currentUser$!: Observable<CurrentUser | null>;
  initials$!: Observable<string>;
  isMenuOpen = false;

  constructor(
    private authService: AuthService,
    private router: Router,
    private elementRef: ElementRef
  ) {
    this.currentUser$ = this.authService.currentUser$;
    this.initials$ = this.currentUser$.pipe(
      map(user => {
        if (!user?.email) return '';
        const name = user.email.split('@')[0];
        return name.charAt(0).toUpperCase();
      })
    );
  }

  toggleMenu(): void {
    this.isMenuOpen = !this.isMenuOpen;
  }

  closeMenu(): void {
    this.isMenuOpen = false;
  }

  // Ecoute tous les clics sur le document : si on clique en dehors du header, on ferme le dropdown.
  // C'est le pattern classique pour un menu dropdown sans librairie externe.
  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    if (!this.isMenuOpen) {
      return;
    }

    if (!this.elementRef.nativeElement.contains(event.target)) {
      this.closeMenu();
    }
  }

  logout(): void {
    this.closeMenu();
    this.authService.logout();
    this.router.navigate(['/login']);
  }
}