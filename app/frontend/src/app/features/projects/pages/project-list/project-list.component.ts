import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { NgFor } from '@angular/common';

@Component({
  selector: 'app-project-list',
  standalone: true,
  imports: [NgFor, RouterLink],
  templateUrl: './project-list.component.html',
  styleUrl: './project-list.component.scss'
})
export class ProjectListComponent {
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