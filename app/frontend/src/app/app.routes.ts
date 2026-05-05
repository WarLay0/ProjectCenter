import { Routes } from '@angular/router';
import { LoginComponent } from './features/auth/pages/login/login.component';
import { RegisterComponent } from './features/auth/pages/register/register.component';
import { ProjectListComponent } from './features/projects/pages/project-list/project-list.component';
import { ProjectDetailComponent } from './features/projects/pages/project-detail/project-detail.component';

export const routes: Routes = [
  { path: '', redirectTo: 'projects', pathMatch: 'full' },

  { path: 'login', component: LoginComponent },
  { path: 'register', component: RegisterComponent },

  { path: 'projects', component: ProjectListComponent },
  { path: 'projects/:id', component: ProjectDetailComponent },

  { path: '**', redirectTo: 'projects' }
];