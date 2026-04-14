import { Component } from '@angular/core';
import { NgFor, NgClass } from '@angular/common';

@Component({
  selector: 'app-project-detail',
  standalone: true,
  imports: [NgFor, NgClass],
  templateUrl: './project-detail.component.html',
  styleUrl: './project-detail.component.scss'
})
export class ProjectDetailComponent {
  project = {
    name: 'ProjectCenter Web',
    description: 'Développement complet du front Angular pour la gestion des projets.',
    progress: 45
  };

  sprints = [
    {
      name: 'Sprint 1 - Initialisation',
      tasks: [
        { name: 'Initialiser le projet Angular', status: 'done' },
        { name: 'Mettre en place le routing', status: 'done' },
        { name: 'Créer la structure des dossiers', status: 'in_progress' }
      ]
    },
    {
      name: 'Sprint 2 - Authentification',
      tasks: [
        { name: 'Créer la page login', status: 'in_progress' },
        { name: 'Brancher le token JWT', status: 'todo' },
        { name: 'Ajouter un auth guard', status: 'todo' }
      ]
    }
  ];
}