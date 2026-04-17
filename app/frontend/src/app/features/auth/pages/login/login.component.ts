import { NgIf } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule, NgIf, RouterLink],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss'
})
export class LoginComponent {
  email = '';
  password = '';
  error = '';
  isSubmitting = false;

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  onSubmit(): void {
    this.error = '';

    if (!this.email || !this.password) {
      this.error = 'Veuillez renseigner votre email et votre mot de passe.';
      return;
    }

    this.isSubmitting = true;

    this.authService.login(this.email, this.password).subscribe({
      next: () => {
        this.authService.getMe().subscribe({
          next: () => {
            this.isSubmitting = false;
            this.router.navigate(['/projects']);
          },
          error: () => {
            this.isSubmitting = false;
            this.error = 'Connexion réussie, mais impossible de récupérer votre profil.';
          }
        });
      },
      error: () => {
        this.isSubmitting = false;
        this.error = 'Identifiants invalides.';
      }
    });
  }
}