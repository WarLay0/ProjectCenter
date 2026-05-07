import { Component, OnInit } from '@angular/core';
import { NgFor, NgIf } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ProjectService, Project } from '../../services/project.service';
import { FormsModule } from '@angular/forms';
import { forkJoin, of } from 'rxjs';
import { LucideAngularModule } from 'lucide-angular';
import { SprintService } from '../../../sprints/services/sprint.service';
import { TaskService } from '../../../tasks/services/task.service';

@Component({
  selector: 'app-project-list',
  standalone: true,
  imports: [NgFor, NgIf, RouterLink, FormsModule, LucideAngularModule],
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

  constructor(
  private projectService: ProjectService,
  private sprintService: SprintService,
  private taskService: TaskService
) {}

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

  // On charge les projets, puis pour chaque projet ses sprints, puis pour chaque sprint ses taches.
  // C'est en cascade parce que l'API n'a pas d'endpoint qui agrege tout d'un coup.
  // forkJoin = on attend que toutes les requetes paralleles soient finies avant de calculer les stats.
  loadProjects(): void {
  this.isLoading = true;
  this.error = '';

  this.projectService.getProjects().subscribe({
    next: (projects) => {
      if (!projects.length) {
        this.projects = [];
        this.resetStats();
        this.isLoading = false;
        return;
      }

      const sprintRequests = projects.map(project =>
        this.sprintService.getSprintsByProject(project.id)
      );

      forkJoin(sprintRequests).subscribe({
        next: (sprintsByProject) => {
          const allSprints = sprintsByProject.flat();

          if (!allSprints.length) {
            this.projects = projects.map(project => ({
              ...project,
              progress: 0
            }));

            this.stats = {
              projects: projects.length,
              activeSprints: 0,
              totalSprints: 0,
              completedTasks: 0,
              totalTasks: 0
            };

            this.isLoading = false;
            return;
          }

          const taskRequests = allSprints.map(sprint =>
            this.taskService.getTasksBySprint(sprint.id)
          );

          forkJoin(taskRequests).subscribe({
            next: (tasksBySprint) => {
              const allTasks = tasksBySprint.flat();

              this.projects = projects.map((project, projectIndex) => {
                const projectSprints = sprintsByProject[projectIndex] ?? [];

                const projectTasks = projectSprints.flatMap(sprint => {
                  const sprintIndex = allSprints.findIndex(item => item.id === sprint.id);
                  return sprintIndex >= 0 ? tasksBySprint[sprintIndex] : [];
                });

                const doneTasks = projectTasks.filter(task => task.status === 'done').length;
                const totalTasks = projectTasks.length;

                return {
                  ...project,
                  progress: totalTasks
                    ? Math.round((doneTasks / totalTasks) * 100)
                    : 0
                };
              });

              this.stats = {
                projects: projects.length,
                activeSprints: allSprints.filter(sprint => sprint.status === 'in_progress').length,
                totalSprints: allSprints.length,
                completedTasks: allTasks.filter(task => task.status === 'done').length,
                totalTasks: allTasks.length
              };

              this.isLoading = false;
            },
            error: () => {
              this.error = 'Impossible de charger les tâches.';
              this.isLoading = false;
            }
          });
        },
        error: () => {
          this.error = 'Impossible de charger les sprints.';
          this.isLoading = false;
        }
      });
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
      this.loadProjects();
    },
    error: () => {
      alert('Erreur lors de la suppression du projet.');
    }
  });
}

  resetStats(): void {
  this.stats = {
    projects: 0,
    activeSprints: 0,
    totalSprints: 0,
    completedTasks: 0,
    totalTasks: 0
  };
}
}