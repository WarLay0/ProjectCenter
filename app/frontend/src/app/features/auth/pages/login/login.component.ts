import { Component } from '@angular/core';
import { AuthService } from '../../services/auth.service';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-login',
  standalone: true,
  templateUrl: './login.component.html',
   imports: [FormsModule]
})
export class LoginComponent {

  email = '';
  password = '';
  error = '';

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  onSubmit() {
    this.authService.login(this.email, this.password).subscribe({
      next: () => {
        this.router.navigate(['/projects']);
      },
      error: () => {
        this.error = 'Identifiants invalides';
      }
    });
  }
}