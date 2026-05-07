import { Component, OnInit } from '@angular/core';
import { NgClass, NgFor, NgIf } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { forkJoin } from 'rxjs';

import { Project, ProjectService } from '../../services/project.service';
import { SprintService } from '../../../sprints/services/sprint.service';
import { TaskService } from '../../../tasks/services/task.service';

type TabType = 'overview' | 'sprints';
type SprintStatus = 'done' | 'in_progress' | 'planned';
type TaskStatus = 'done' | 'in_progress' | 'todo';

interface SprintTask {
  id: string;
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
  position?: number;
  isOpen: boolean;
  tasks: SprintTask[];
  startDate?: string;
  endDate?: string;
}

interface TeamMember {
  initials: string;
  name: string;
  tasks: string;
}

interface PlanningRow {
  name: string;
  left: string;
  width: string;
  label: string;
  className: string;
}

@Component({
  selector: 'app-project-detail',
  standalone: true,
  imports: [NgFor, NgClass, NgIf, RouterLink, FormsModule],
  templateUrl: './project-detail.component.html',
  styleUrl: './project-detail.component.scss'
})
export class ProjectDetailComponent implements OnInit {
  activeTab: TabType = 'overview';

  isLoading = false;
  error = '';

  planningStartLabel = '';
  planningEndLabel = '';

  project: Project = {
    id: '',
    name: '',
    description: '',
    progress: 0
  };

  stats = {
    activeSprints: 0,
    completed: 0,
    planned: 0,
    progress: 0
  };

  currentSprint = {
    name: 'Aucun sprint en cours',
    description: '',
    progress: 0,
    period: '',
    tasks: '0/0 tâches'
  };
  addingTaskSprintId: string | null = null;

newTask = {
  name: '',
  assignee: '',
  status: 'todo' as TaskStatus
};

  taskDistribution = [
    { label: 'Terminées', done: 0, total: 0, width: 0, className: 'done' },
    { label: 'En cours', done: 0, total: 0, width: 0, className: 'in-progress' },
    { label: 'À faire', done: 0, total: 0, width: 0, className: 'todo' }
  ];

  team: TeamMember[] = [];
  planningRows: PlanningRow[] = [];
  sprints: SprintItem[] = [];

  showSprintForm = false;
  editingSprintId: string | null = null;

  newSprint = {
    name: '',
    description: '',
    startDate: '',
    endDate: '',
    status: 'planned' as SprintStatus,
    tasks: [
      {
        name: '',
        assignee: '',
        status: 'todo' as TaskStatus
      }
    ]
  };

  editSprint = {
    name: '',
    description: '',
    startDate: '',
    endDate: '',
    status: 'planned' as SprintStatus
  };

  constructor(
    private route: ActivatedRoute,
    private projectService: ProjectService,
    private sprintService: SprintService,
    private taskService: TaskService
  ) {}

  ngOnInit(): void {
    const projectId = this.route.snapshot.paramMap.get('id');

    if (!projectId) {
      this.error = 'Projet introuvable.';
      return;
    }

    this.loadProject(projectId);
    this.loadSprints(projectId);
    
  }

  loadProject(id: string): void {
    this.isLoading = true;
    this.error = '';

    this.projectService.getProjectById(id).subscribe({
      next: (project) => {
        this.project = project;
        this.isLoading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le projet.';
        this.isLoading = false;
      }
    });
  }

  loadSprints(projectId: string): void {
    this.sprintService.getSprintsByProject(projectId).subscribe({
      next: (sprints) => {
        if (!sprints.length) {
          this.sprints = [];
          this.updateSprintStats();
          this.updatePlanningRows();
          this.updateOverviewData();
          return;
        }

        const requests = sprints.map(sprint =>
          this.taskService.getTasksBySprint(sprint.id)
        );

        forkJoin(requests).subscribe({
          next: (tasksBySprint) => {
            this.sprints = sprints.map((sprint, index) => {
              const tasks = tasksBySprint[index] ?? [];
              const doneTasks = tasks.filter(task => task.status === 'done').length;
              const totalTasks = tasks.length;
              const progress = totalTasks ? Math.round((doneTasks / totalTasks) * 100) : 0;

              return {
                id: sprint.id,
                name: sprint.name,
                badgeLabel: this.getSprintStatusLabel(sprint.status ?? 'planned'),
                badgeClass: sprint.status ?? 'planned',
                period: this.formatSprintPeriod(sprint.startDate, sprint.endDate),
                taskProgress: `${doneTasks}/${totalTasks} tâches`,
                progress,
                description: sprint.description ?? '',
                position: sprint.position,
                startDate: sprint.startDate,
                endDate: sprint.endDate,
                isOpen: index === 0,
               tasks: tasks.map(task => ({
                  id: task.id,
                  name: task.name,
                  assignee: task.assignee ?? '',
                  status: task.status
                }))
              };
            });

            this.updateSprintStats();
            this.updatePlanningRows();
            this.updateOverviewData();
          },
          error: () => {
            this.error = 'Impossible de charger les tâches.';
          }
        });
      },
      error: () => {
        this.error = 'Impossible de charger les sprints.';
      }
    });
  }

  setActiveTab(tab: TabType): void {
    this.activeTab = tab;
  }

  toggleSprint(id: string): void {
    this.sprints = this.sprints.map(sprint =>
      sprint.id === id
        ? { ...sprint, isOpen: !sprint.isOpen }
        : sprint
    );
  }

  toggleSprintForm(): void {
    this.showSprintForm = !this.showSprintForm;
  }

  addTaskField(): void {
    this.newSprint.tasks.push({
      name: '',
      assignee: '',
      status: 'todo'
    });
  }

  removeTaskField(index: number): void {
    this.newSprint.tasks.splice(index, 1);
  }

  getNextSprintPosition(): number {
    if (!this.sprints.length) {
      return 1;
    }

    const maxPosition = Math.max(
      ...this.sprints.map(sprint => sprint.position ?? 0)
    );

    return maxPosition + 1;
  }

  createSprint(): void {
    if (!this.project.id || !this.newSprint.name.trim()) return;

    const projectId = this.project.id;

    this.sprintService.createSprint({
      name: this.newSprint.name,
      description: this.newSprint.description,
      position: this.getNextSprintPosition(),
      status: this.newSprint.status,
      startDate: this.newSprint.startDate
        ? `${this.newSprint.startDate}T00:00:00+00:00`
        : null,
      endDate: this.newSprint.endDate
        ? `${this.newSprint.endDate}T00:00:00+00:00`
        : null,
      project: `/api/projects/${projectId}`
    }).subscribe({
      next: (createdSprint: any) => {
        const sprintIri = createdSprint['@id'] ?? `/api/sprints/${createdSprint.id}`;

        const validTasks = this.newSprint.tasks.filter(task => task.name.trim());

        if (!validTasks.length) {
          this.afterSprintCreated(projectId);
          return;
        }

        const taskRequests = validTasks.map((task, index) =>
          this.taskService.createTask({
            name: task.name,
            assignee: task.assignee,
            status: task.status,
            position: index + 1,
            sprint: sprintIri
          })
        );

        forkJoin(taskRequests).subscribe({
          next: () => this.afterSprintCreated(projectId),
          error: () => alert('Sprint créé, mais erreur lors de la création des tâches.')
        });
      },
      error: () => {
        alert('Erreur lors de la création du sprint.');
      }
    });
  }

  afterSprintCreated(projectId: string): void {
    this.loadSprints(projectId);
    this.resetSprintForm();
  }

  resetSprintForm(): void {
    this.newSprint = {
      name: '',
      description: '',
      startDate: '',
      endDate: '',
      status: 'planned',
      tasks: [
        {
          name: '',
          assignee: '',
          status: 'todo'
        }
      ]
    };

    this.showSprintForm = false;
  }

  startEditSprint(sprint: SprintItem): void {
    this.editingSprintId = sprint.id;

    this.sprints = this.sprints.map(item =>
      item.id === sprint.id
        ? { ...item, isOpen: true }
        : item
    );

    this.editSprint = {
      name: sprint.name,
      description: sprint.description ?? '',
      startDate: sprint.startDate ? sprint.startDate.substring(0, 10) : '',
      endDate: sprint.endDate ? sprint.endDate.substring(0, 10) : '',
      status: sprint.badgeClass
    };
  }

  cancelEditSprint(): void {
    this.editingSprintId = null;

    this.editSprint = {
      name: '',
      description: '',
      startDate: '',
      endDate: '',
      status: 'planned'
    };
  }

  updateSprint(id: string): void {
    if (!this.editSprint.name.trim()) return;

    this.sprintService.updateSprint(id, {
      name: this.editSprint.name,
      description: this.editSprint.description,
      status: this.editSprint.status,
      startDate: this.editSprint.startDate
        ? `${this.editSprint.startDate}T00:00:00+00:00`
        : undefined,
      endDate: this.editSprint.endDate
        ? `${this.editSprint.endDate}T00:00:00+00:00`
        : undefined
    }).subscribe({
      next: () => {
        this.cancelEditSprint();
        this.loadSprints(this.project.id);
      },
      error: () => {
        alert('Erreur lors de la modification du sprint.');
      }
    });
  }

  deleteSprint(id: string): void {
    if (!confirm('Supprimer ce sprint et ses tâches ?')) return;

    this.sprintService.deleteSprint(id).subscribe({
      next: () => {
        this.loadSprints(this.project.id);
      },
      error: () => {
        alert('Erreur lors de la suppression du sprint.');
      }
    });
  }

  updateSprintStats(): void {
    this.stats = {
      activeSprints: this.sprints.filter(sprint => sprint.badgeClass === 'in_progress').length,
      completed: this.sprints.filter(sprint => sprint.badgeClass === 'done').length,
      planned: this.sprints.filter(sprint => sprint.badgeClass === 'planned').length,
     progress: this.getGlobalProgress()
    };

    const current = this.sprints.find(sprint => sprint.badgeClass === 'in_progress') ?? this.sprints[0];

    if (current) {
      this.currentSprint = {
        name: current.name,
        description: current.description,
        progress: current.progress,
        period: current.period,
        tasks: current.taskProgress
      };
    }
  }

  updatePlanningRows(): void {
    const datedSprints = this.sprints.filter(
      sprint => sprint.startDate && sprint.endDate
    );

    if (!datedSprints.length) {
      this.planningRows = [];
      this.planningStartLabel = '';
      this.planningEndLabel = '';
      return;
    }

    const projectStart = Math.min(
      ...datedSprints.map(sprint => new Date(sprint.startDate!).getTime())
    );

    const projectEnd = Math.max(
      ...datedSprints.map(sprint => new Date(sprint.endDate!).getTime())
    );

    const paddingDays = 7;
    const paddingMs = paddingDays * 24 * 60 * 60 * 1000;

    const timelineStart = projectStart - paddingMs;
    const timelineEnd = projectEnd + paddingMs;

    this.planningStartLabel = this.formatMonthLabel(new Date(timelineStart));
    this.planningEndLabel = this.formatMonthLabel(new Date(timelineEnd));

    const totalDuration = timelineEnd - timelineStart;

    if (totalDuration <= 0) {
      this.planningRows = [];
      return;
    }

    this.planningRows = datedSprints.map(sprint => {
      const sprintStart = new Date(sprint.startDate!).getTime();
      const sprintEnd = new Date(sprint.endDate!).getTime();

      const left = ((sprintStart - timelineStart) / totalDuration) * 100;
      const width = ((sprintEnd - sprintStart) / totalDuration) * 100;

      return {
        name: sprint.name,
        left: `${left}%`,
        width: `${Math.max(width, 8)}%`,
        label: `${sprint.progress}%`,
        className:
          sprint.badgeClass === 'done'
            ? 'done'
            : sprint.badgeClass === 'in_progress'
              ? 'in-progress'
              : 'todo'
      };
    });
  }

  getSprintStatusLabel(status: SprintStatus): string {
    switch (status) {
      case 'done':
        return 'Terminé';
      case 'in_progress':
        return 'En cours';
      default:
        return 'Planifié';
    }
  }

  formatSprintPeriod(startDate?: string, endDate?: string): string {
    if (!startDate && !endDate) {
      return 'Non défini';
    }

    const start = startDate ? this.formatDate(startDate) : '?';
    const end = endDate ? this.formatDate(endDate) : '?';

    return `${start} → ${end}`;
  }

  formatDate(date: string): string {
    return new Intl.DateTimeFormat('fr-FR', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    }).format(new Date(date));
  }

  formatMonthLabel(date: Date): string {
    return new Intl.DateTimeFormat('fr-FR', {
      month: 'short',
      year: 'numeric'
    }).format(date);
  }

  editingTaskId: string | null = null;

editTask = {
  name: '',
  assignee: '',
  status: 'todo' as TaskStatus
};

startEditTask(task: SprintTask): void {
  this.editingTaskId = task.id;

  this.editTask = {
    name: task.name,
    assignee: task.assignee ?? '',
    status: task.status
  };
}

cancelEditTask(): void {
  this.editingTaskId = null;

  this.editTask = {
    name: '',
    assignee: '',
    status: 'todo'
  };
}

updateTask(id: string): void {
  if (!this.editTask.name.trim()) return;

  this.taskService.updateTask(id, {
    name: this.editTask.name,
    assignee: this.editTask.assignee,
    status: this.editTask.status
  }).subscribe({
    next: () => {
      this.cancelEditTask();
      this.loadSprints(this.project.id);
    },
    error: () => {
      alert('Erreur lors de la modification de la tâche.');
    }
  });
}

deleteTask(id: string): void {
  if (!confirm('Supprimer cette tâche ?')) return;

  this.taskService.deleteTask(id).subscribe({
    next: () => {
      this.loadSprints(this.project.id);
    },
    error: () => {
      alert('Erreur lors de la suppression de la tâche.');
    }
  });
}

startAddTask(sprintId: string): void {
  this.addingTaskSprintId = sprintId;

  this.newTask = {
    name: '',
    assignee: '',
    status: 'todo'
  };
}

cancelAddTask(): void {
  this.addingTaskSprintId = null;

  this.newTask = {
    name: '',
    assignee: '',
    status: 'todo'
  };
}

getNextTaskPosition(sprint: SprintItem): number {
  return sprint.tasks.length + 1;
}

createTaskInSprint(sprint: SprintItem): void {
  if (!this.newTask.name.trim()) return;

  this.taskService.createTask({
    name: this.newTask.name,
    assignee: this.newTask.assignee,
    status: this.newTask.status,
    position: this.getNextTaskPosition(sprint),
    sprint: `/api/sprints/${sprint.id}`
  }).subscribe({
    next: () => {
      this.cancelAddTask();
      this.loadSprints(this.project.id);
    },
    error: () => {
      alert('Erreur lors de la création de la tâche.');
    }
  });
}
updateOverviewData(): void {
  const allTasks = this.sprints.flatMap(sprint => sprint.tasks);
  const totalTasks = allTasks.length;

  const doneTasks = allTasks.filter(task => task.status === 'done').length;
  const inProgressTasks = allTasks.filter(task => task.status === 'in_progress').length;
  const todoTasks = allTasks.filter(task => task.status === 'todo').length;

  this.taskDistribution = [
    {
      label: 'Terminées',
      done: doneTasks,
      total: totalTasks,
      width: totalTasks ? Math.round((doneTasks / totalTasks) * 100) : 0,
      className: 'done'
    },
    {
      label: 'En cours',
      done: inProgressTasks,
      total: totalTasks,
      width: totalTasks ? Math.round((inProgressTasks / totalTasks) * 100) : 0,
      className: 'in-progress'
    },
    {
      label: 'À faire',
      done: todoTasks,
      total: totalTasks,
      width: totalTasks ? Math.round((todoTasks / totalTasks) * 100) : 0,
      className: 'todo'
    }
  ];

  const teamMap = new Map<string, { total: number; done: number }>();

  allTasks.forEach(task => {
    const assignee = task.assignee?.trim() || 'Non assigné';

    const current = teamMap.get(assignee) ?? {
      total: 0,
      done: 0
    };

    current.total++;

    if (task.status === 'done') {
      current.done++;
    }

    teamMap.set(assignee, current);
  });

  this.team = Array.from(teamMap.entries()).map(([name, values]) => ({
    name,
    initials: name.charAt(0).toUpperCase(),
    tasks: `${values.done}/${values.total}`
  }));
}

getGlobalProgress(): number {
  const allTasks = this.sprints.flatMap(sprint => sprint.tasks);

  if (!allTasks.length) {
    return 0;
  }

  const doneTasks = allTasks.filter(task => task.status === 'done').length;

  return Math.round((doneTasks / allTasks.length) * 100);
}
}