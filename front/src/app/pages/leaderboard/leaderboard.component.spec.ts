import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { of, throwError } from 'rxjs';

import { LeaderboardComponent } from './leaderboard.component';
import { environment } from '../../../environments/environment';

describe('LeaderboardComponent', () => {
  let component: LeaderboardComponent;
  let fixture: ComponentFixture<LeaderboardComponent>;
  let httpMock: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [LeaderboardComponent, HttpClientTestingModule],
    }).compileComponents();

    fixture = TestBed.createComponent(LeaderboardComponent);
    component = fixture.componentInstance;
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component.leaderboardData).toBeNull();
    expect(component.isLoading).toBeTrue();
    expect(component.error).toBeNull();
  });

  it('should load leaderboard on init', () => {
    const mockResponse = {
      leaderboard: [
        {
          id: 1,
          pseudo: 'user1',
          firstName: 'User',
          lastName: 'One',
          avatar: 'avatar1',
          totalScore: 1000,
          averageScore: 85,
          quizzesCompleted: 10,
          badgesCount: 5,
          rankingScore: 1000,
          position: 1,
          memberSince: '2024-01-01',
          isCurrentUser: false,
        },
      ],
      currentUser: {
        position: 1,
        data: null,
        totalUsers: 1,
      },
      meta: {
        totalUsers: 1,
        limit: 10,
        generatedAt: '2024-01-01T00:00:00Z',
      },
    };

    component.ngOnInit();

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/leaderboard?limit=10`);
    expect(req.request.method).toBe('GET');
    req.flush(mockResponse);

    expect(component.leaderboardData).toEqual(mockResponse);
    expect(component.isLoading).toBeFalse();
  });

  it('should handle error when loading leaderboard', () => {
    component.ngOnInit();

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/leaderboard?limit=10`);
    req.flush('Error', { status: 500, statusText: 'Internal Server Error' });

    expect(component.error).toBe('Impossible de charger le classement');
    expect(component.isLoading).toBeFalse();
  });

  it('should get medal icon for position 1', () => {
    expect(component.getMedalIcon(1)).toBe('fas fa-trophy');
  });

  it('should get medal icon for position 2', () => {
    expect(component.getMedalIcon(2)).toBe('fas fa-medal');
  });

  it('should get medal icon for position 3', () => {
    expect(component.getMedalIcon(3)).toBe('fas fa-award');
  });

  it('should get empty medal icon for other positions', () => {
    expect(component.getMedalIcon(4)).toBe('');
  });

  it('should get medal class for position 1', () => {
    expect(component.getMedalClass(1)).toBe('gold');
  });

  it('should get medal class for position 2', () => {
    expect(component.getMedalClass(2)).toBe('silver');
  });

  it('should get medal class for position 3', () => {
    expect(component.getMedalClass(3)).toBe('bronze');
  });

  it('should get empty medal class for other positions', () => {
    expect(component.getMedalClass(4)).toBe('');
  });

  it('should get user avatar shape', () => {
    const user = { avatarShape: 'blob_flower' } as any;
    expect(component.getUserAvatarShape(user)).toBe('blob_flower');
  });

  it('should get default avatar shape when not provided', () => {
    const user = {} as any;
    expect(component.getUserAvatarShape(user)).toBe('blob_circle');
  });

  it('should get user avatar color', () => {
    const user = { avatarColor: '#FF0000' } as any;
    expect(component.getUserAvatarColor(user)).toBe('#FF0000');
  });

  it('should get default avatar color when not provided', () => {
    const user = {} as any;
    expect(component.getUserAvatarColor(user)).toBe('#257D54');
  });

  it('should get position suffix for 1st', () => {
    expect(component.getPositionSuffix(1)).toBe('er');
  });

  it('should get position suffix for 2nd', () => {
    expect(component.getPositionSuffix(2)).toBe('ème');
  });

  it('should get position suffix for 11th', () => {
    expect(component.getPositionSuffix(11)).toBe('ème');
  });

  it('should get player level for expert', () => {
    expect(component.getPlayerLevel(1000)).toBe('Expert');
  });

  it('should get player level for advanced', () => {
    expect(component.getPlayerLevel(500)).toBe('Avancé');
  });

  it('should get player level for intermediate', () => {
    expect(component.getPlayerLevel(200)).toBe('Intermédiaire');
  });

  it('should get player level for beginner+', () => {
    expect(component.getPlayerLevel(50)).toBe('Débutant+');
  });

  it('should get player level for novice', () => {
    expect(component.getPlayerLevel(10)).toBe('Novice');
  });

  it('should get head avatar from shape', () => {
    expect(component.getHeadAvatarFromShape('blob_flower')).toBe('flower_head');
    expect(component.getHeadAvatarFromShape('blob_circle')).toBe('circle_head');
    expect(component.getHeadAvatarFromShape('blob_pic')).toBe('pic_head');
    expect(component.getHeadAvatarFromShape('blob_wave')).toBe('wave_head');
  });

  it('should get default head avatar for unknown shape', () => {
    expect(component.getHeadAvatarFromShape('unknown')).toBe('head_guest');
    expect(component.getHeadAvatarFromShape('')).toBe('head_guest');
  });

  it('should track by player id', () => {
    const player = { id: 123 } as any;
    expect(component.trackByPlayerId(0, player)).toBe(123);
  });
});
