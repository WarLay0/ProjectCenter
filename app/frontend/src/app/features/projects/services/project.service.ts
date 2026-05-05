import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../../environments/environment';

export interface Project {
  id: string;
  name: string;
  description?: string;
  progress?: number;
}

@Injectable({
  providedIn: 'root'
})
export class ProjectService {
  private apiUrl = `${environment.apiUrl}/api`;

  constructor(private http: HttpClient) {}

getProjects(): Observable<Project[]> {
  return this.http.get<any>(`${this.apiUrl}/projects`).pipe(
    map(response => response.member ?? response['hydra:member'] ?? response),
    map((projects: any[]) =>
      projects.map(project => ({
        id: project.id ?? project.uuid ?? project['@id']?.split('/').pop(),
        name: project.name,
        description: project.description,
        progress: project.progress ?? 0
      }))
    )
  );
}

createProject(data: { name: string; description?: string }): Observable<any> {
  return this.http.post(`${this.apiUrl}/projects`, data);
}

  deleteProject(id: string): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/projects/${id}`);
  }

  updateProject(id: string, data: Partial<Project>): Observable<Project> {
  return this.http.patch<Project>(
    `${this.apiUrl}/projects/${id}`,
    data,
    {
      headers: {
        'Content-Type': 'application/merge-patch+json'
      }
    }
  );
}
}