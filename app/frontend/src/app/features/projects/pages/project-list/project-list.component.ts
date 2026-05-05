import { Component } from '@angular/core';
import { NgFor } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-project-list',
  standalone: true,
  imports: [NgFor, RouterLink],
  templateUrl: './project-list.component.html',
  styleUrl: './project-list.component.scss'
})
export class ProjectListComponent {
  stats = {
    projects: 4,
    activeSprints: 4,
    totalSprints: 10,
    completedTasks: 14,
    totalTasks: 36
  };

  projects = [
    {
      id: '1',
      name: 'ProjectCenter Web',
      description: 'Développement de l’interface Angular principale.',
      progress: 45
    },
    {
      id: '2',
      name: 'API Collaboration',
      description: 'Mise en place des workflows de validation des tâches.',
      progress: 70
    },
    {
      id: '3',
      name: 'Dashboard Interne',
      description: 'Visualisation globale de l’état d’avancement des projets.',
      progress: 25
    }
  ];
}