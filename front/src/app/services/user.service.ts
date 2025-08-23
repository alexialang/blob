import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';
import { tap, catchError, map, switchMap } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { User, Badge } from '../models/user.interface';
import { AuthService } from './auth.service';

@Injectable({ providedIn: 'root' })
export class UserService {
  private readonly baseUrl = `${environment.apiBaseUrl}/user`;
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {}

  getCurrentUser(): User | null {
    return this.currentUserSubject.value;
  }

  setCurrentUser(user: User): void {
    this.currentUserSubject.next(user);
  }

  getUserProfile(): Observable<User> {
    return this.http.get<User>(`${this.baseUrl}/profile`).pipe(
      tap(user => this.setCurrentUser(user))
    );
  }

  getUserProfileById(userId: number): Observable<User> {
    return this.authService.hasPermission('VIEW_RESULTS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission VIEW_RESULTS requise');
        }
        return this.http.get<User>(`${this.baseUrl}/${userId}`);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  updateUserProfile(userData: Partial<User>): Observable<User> {
    return this.http.put<User>(`${this.baseUrl}/profile/update`, userData).pipe(
      tap(user => this.setCurrentUser(user))
    );
  }

  getUserStatistics(): Observable<any> {
    return this.http.get<any>(`${this.baseUrl}/statistics`);
  }

  updateAvatar(avatar: { shape: string; color: string }): Observable<User> {
    return this.updateUserProfile({ avatarShape: avatar.shape, avatarColor: avatar.color });
  }

}
