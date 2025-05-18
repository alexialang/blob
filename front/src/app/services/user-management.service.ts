import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment.development';

@Injectable({ providedIn: 'root' })
export class UserManagementService {
  constructor(private http: HttpClient) {}

  getUsers(): Observable<any[]> {
    console.log(environment.apiBaseUrl);
    return this.http.get<any[]>(`${environment.apiBaseUrl}/user/n`);
  }

}
