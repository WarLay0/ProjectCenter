import { Component, OnInit } from '@angular/core';
import { NgFor, NgIf } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ProjectService, Project } from '../../services/project.service';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-project-list',
  standalone: true,
  imports: [NgFor, NgIf, RouterLink,FormsModule],
  templateUrl: './project-list.component.html',
  styleUrl: './project-list.component.scss'
})
export class ProjectListComponent implements OnInit {
  isLoading = false;
  error = '';

  stats = {
    projects: 0,
    activeSprints: 0,
    totalSprints: 0,
    completedTasks: 0,
    totalTasks: 0
  };
  showForm = false;
  editingProjectId: string | null = null;
  projects: Project[] = [];

  constructor(private projectService: ProjectService) {}

  ngOnInit(): void {
    this.loadProjects();
  }
 
  newProject = {name: '', description: ''};
  editProject = {name: '',description: ''};

  toggleForm(): void {
  this.showForm = !this.showForm;
}

createProject(): void {
  if (!this.newProject.name.trim()) return;

  this.projectService.createProject(this.newProject).subscribe({
    next: () => {
      this.loadProjects();

      this.newProject = {
        name: '',
        description: ''
      };

      this.showForm = false;
    },
    error: () => {
      alert('Erreur lors de la création du projet.');
    }
  });
}

  loadProjects(): void {
    this.isLoading = true;
    this.error = '';

    this.projectService.getProjects().subscribe({
      next: (projects) => {
        this.projects = projects.map(project => ({
          ...project,
          progress: project.progress ?? 0
        }));

        this.stats.projects = this.projects.length;
        this.isLoading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les projets.';
        this.isLoading = false;
      }
    });
  }

  startEdit(project: Project): void {
  this.editingProjectId = project.id;
  this.editProject = {
    name: project.name,
    description: project.description ?? ''
  };
}

cancelEdit(): void {
  this.editingProjectId = null;
  this.editProject = {
    name: '',
    description: ''
  };
}

updateProject(id: string): void {
  if (!this.editProject.name.trim()) return;

  this.projectService.updateProject(id, this.editProject).subscribe({
    next: () => {
      this.loadProjects();
      this.cancelEdit();
    },
    error: () => {
      alert('Erreur lors de la modification du projet.');
    }
  });
}

  deleteProject(id: string): void {
    if (!confirm('Supprimer ce projet ?')) return;

    this.projectService.deleteProject(id).subscribe({
      next: () => {
        this.projects = this.projects.filter(project => project.id !== id);
        this.stats.projects = this.projects.length;
      },
      error: () => {
        alert('Erreur lors de la suppression du projet.');
      }
    });
  }
}