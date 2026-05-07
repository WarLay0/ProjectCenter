import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../../environments/environment';

export interface Task {
  id: string;
  name: string;
  description?: string;
  status: 'todo' | 'in_progress' | 'done';
  position?: number;
  assignee?: string;
  sprint?: string;
}

@Injectable({
  providedIn: 'root'
})
export class TaskService {
  private apiUrl = `${environment.apiUrl}/api`;

  constructor(private http: HttpClient) {}

  createTask(data: {
    name: string;
    description?: string;
    status: string;
    position: number;
    assignee?: string;
    sprint: string;
  }): Observable<Task> {
    return this.http.post<Task>(`${this.apiUrl}/tasks`, data);
  }
  // Meme principe que pour les sprints : on filtre par IRI du sprint (encodee).
  getTasksBySprint(sprintId: string): Observable<Task[]> {
  const sprintIri = encodeURIComponent(`/api/sprints/${sprintId}`);

  return this.http.get<any>(`${this.apiUrl}/tasks?sprint=${sprintIri}`).pipe(
    map(response => response.member ?? response['hydra:member'] ?? response),
    map((tasks: any[]) =>
      tasks.map(task => ({
        id: task.id ?? task.uuid ?? task['@id']?.split('/').pop(),
        name: task.name,
        description: task.description,
        status: task.status,
        position: task.position,
        assignee: task.assignee ?? '',
        sprint: task.sprint
      }))
    )
  );
}
updateTask(id: string, data: Partial<Task>): Observable<Task> {
  return this.http.patch<Task>(
    `${this.apiUrl}/tasks/${id}`,
    data,
    {
      headers: {
        'Content-Type': 'application/merge-patch+json'
      }
    }
  );
}

deleteTask(id: string): Observable<void> {
  return this.http.delete<void>(`${this.apiUrl}/tasks/${id}`);
}
}