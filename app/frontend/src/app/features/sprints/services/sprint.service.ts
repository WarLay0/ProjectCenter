import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../../environments/environment';

export interface Sprint {
  id: string;
  name: string;
  description?: string;
  position?: number;
  project?: string;
  progress?: number;
  isOpen?: boolean;
  status?: 'planned' | 'in_progress' | 'done';
  startDate?: string;
  endDate?: string;
}

@Injectable({
  providedIn: 'root'
})
export class SprintService {
  private apiUrl = `${environment.apiUrl}/api`;

  constructor(private http: HttpClient) {}

  getSprintsByProject(projectId: string): Observable<Sprint[]> {
    const projectIri = encodeURIComponent(`/api/projects/${projectId}`);

    return this.http.get<any>(`${this.apiUrl}/sprints?project=${projectIri}`).pipe(
      map(response => response.member ?? response['hydra:member'] ?? response),
      map((sprints: any[]) =>
        sprints.map(sprint => ({
          id: sprint.id ?? sprint.uuid ?? sprint['@id']?.split('/').pop(),
          name: sprint.name,
          description: sprint.description,
          position: sprint.position,
          project: sprint.project,
          progress: sprint.progress ?? 0,
          isOpen: false,
          status: sprint.status ?? 'planned',
          startDate: sprint.startDate,
          endDate: sprint.endDate,
        }))
      )
    );
  }

createSprint(data: {
  name: string;
  description?: string;
  position: number;
  status: string;
  startDate?: string | null;
  endDate?: string | null;
  project: string;
}): Observable<Sprint> {
  return this.http.post<Sprint>(`${this.apiUrl}/sprints`, data);
}

 updateSprint(id: string, data: Partial<Sprint>): Observable<Sprint> {
  return this.http.patch<Sprint>(
    `${this.apiUrl}/sprints/${id}`,
    data,
    {
      headers: {
        'Content-Type': 'application/merge-patch+json'
      }
    }
  );
}

deleteSprint(id: string): Observable<void> {
  return this.http.delete<void>(`${this.apiUrl}/sprints/${id}`);
}
  
}