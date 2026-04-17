import { Component } from '@angular/core';
import { NgClass, NgFor, NgIf } from '@angular/common';
import { RouterLink } from '@angular/router';

type TabType = 'overview' | 'sprints';
type SprintStatus = 'done' | 'in_progress' | 'planned';
type TaskStatus = 'done' | 'in_progress' | 'todo';

interface SprintTask {
  name: string;
  assignee: string;
  status: TaskStatus;
}

interface SprintItem {
  id: string;
  name: string;
  badgeLabel: string;
  badgeClass: SprintStatus;
  period: string;
  taskProgress: string;
  progress: number;
  description: string;
  isOpen: boolean;
  tasks: SprintTask[];
}

@Component({
  selector: 'app-project-detail',
  standalone: true,
  imports: [NgFor, NgClass, NgIf, RouterLink],
  templateUrl: './project-detail.component.html',
  styleUrl: './project-detail.component.scss'
})
export class ProjectDetailComponent {
  activeTab: TabType = 'overview';

  project = {
    name: 'App Mobile E-Commerce',
    description: 'Application mobile de vente en ligne avec paiement intégré et suivi de commandes.'
  };

  stats = {
    activeSprints: 1,
    completed: 2,
    planned: 1,
    progress: 56
  };

  currentSprint = {
    name: 'Sprint 3 — Panier & Paiement',
    description: 'Gestion du panier d\'achat et intégration du système de paiement.',
    progress: 20,
    period: '16 déc. → 5 janv. 2026',
    tasks: '1/5 tâches'
  };

  taskDistribution = [
    { label: 'Terminées', done: 9, total: 16, width: 56, className: 'done' },
    { label: 'En cours', done: 2, total: 16, width: 13, className: 'in-progress' },
    { label: 'À faire', done: 5, total: 16, width: 31, className: 'todo' }
  ];

  team = [
    { initials: 'M', name: 'Marie', tasks: '3/6' },
    { initials: 'L', name: 'Lucas', tasks: '4/6' },
    { initials: 'S', name: 'Sophie', tasks: '2/4' }
  ];

  planningRows = [
    {
      name: 'Sprint 1 — Setup & Auth',
      left: '7%',
      width: '18%',
      label: '100%',
      className: 'done'
    },
    {
      name: 'Sprint 2 — Catalogue Produits',
      left: '26%',
      width: '18%',
      label: '100%',
      className: 'done'
    },
    {
      name: 'Sprint 3 — Panier & Paiement',
      left: '45%',
      width: '28%',
      label: '20%',
      className: 'in-progress'
    },
    {
      name: 'Sprint 4 — Notifications & Po...',
      left: '74%',
      width: '18%',
      label: '0%',
      className: 'todo'
    }
  ];

  sprints: SprintItem[] = [
    {
      id: '1',
      name: 'Sprint 1 — Setup & Auth',
      badgeLabel: 'Terminé',
      badgeClass: 'done',
      period: '18 nov. → 1 déc. 2025',
      taskProgress: '4/4 tâches',
      progress: 100,
      description: 'Mise en place de l\'architecture et du système d\'authentification.',
      isOpen: true,
      tasks: [
        { name: 'Setup projet React Native', assignee: 'Marie', status: 'done' },
        { name: 'Écran de connexion', assignee: 'Lucas', status: 'done' },
        { name: 'Écran d\'inscription', assignee: 'Lucas', status: 'done' },
        { name: 'Intégration API Auth', assignee: 'Marie', status: 'done' }
      ]
    },
    {
      id: '2',
      name: 'Sprint 2 — Catalogue Produits',
      badgeLabel: 'Terminé',
      badgeClass: 'done',
      period: '2 déc. → 15 déc. 2025',
      taskProgress: '4/4 tâches',
      progress: 100,
      description: 'Création du catalogue, fiches produits et structure de navigation.',
      isOpen: false,
      tasks: [
        { name: 'Liste produits', assignee: 'Marie', status: 'done' },
        { name: 'Fiche produit', assignee: 'Sophie', status: 'done' },
        { name: 'Filtre catégories', assignee: 'Lucas', status: 'done' },
        { name: 'Recherche produits', assignee: 'Marie', status: 'done' }
      ]
    },
    {
      id: '3',
      name: 'Sprint 3 — Panier & Paiement',
      badgeLabel: 'En cours',
      badgeClass: 'in_progress',
      period: '16 déc. → 5 janv. 2026',
      taskProgress: '1/5 tâches',
      progress: 20,
      description: 'Gestion du panier d\'achat et intégration du système de paiement.',
      isOpen: false,
      tasks: [
        { name: 'Gestion du panier', assignee: 'Lucas', status: 'done' },
        { name: 'Résumé de commande', assignee: 'Sophie', status: 'in_progress' },
        { name: 'Intégration Stripe', assignee: 'Marie', status: 'todo' },
        { name: 'Validation paiement', assignee: 'Lucas', status: 'todo' },
        { name: 'Gestion erreurs paiement', assignee: 'Marie', status: 'todo' }
      ]
    },
    {
      id: '4',
      name: 'Sprint 4 — Notifications & Polish',
      badgeLabel: 'Planifié',
      badgeClass: 'planned',
      period: '6 janv. → 19 janv. 2026',
      taskProgress: '0/3 tâches',
      progress: 0,
      description: 'Notifications push et polissage de l\'interface utilisateur.',
      isOpen: false,
      tasks: [
        { name: 'Notifications push', assignee: 'Sophie', status: 'todo' },
        { name: 'Optimisation performances', assignee: 'Marie', status: 'todo' },
        { name: 'Tests E2E', assignee: 'Lucas', status: 'todo' }
      ]
    }
  ];

  setActiveTab(tab: TabType): void {
    this.activeTab = tab;
  }

  toggleSprint(id: string): void {
    this.sprints = this.sprints.map((sprint) =>
      sprint.id === id
        ? { ...sprint, isOpen: !sprint.isOpen }
        : sprint
    );
  }
}