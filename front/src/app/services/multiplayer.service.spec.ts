import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { NgZone } from '@angular/core';
import { MultiplayerService, GameRoom } from './multiplayer.service';
import { MercureService } from './mercure.service';
import { environment } from '../../environments/environment';

describe('MultiplayerService', () => {
  let service: MultiplayerService;
  let httpMock: HttpTestingController;
  let mockMercureService: jasmine.SpyObj<MercureService>;
  let mockNgZone: jasmine.SpyObj<NgZone>;

  beforeEach(() => {
    const mercureServiceSpy = jasmine.createSpyObj('MercureService', ['connect'], {
      invitationReceived$: {
        subscribe: jasmine.createSpy('subscribe'),
      },
    });
    const ngZoneSpy = jasmine.createSpyObj('NgZone', ['run'], {
      onStable: {
        subscribe: jasmine.createSpy('subscribe'),
      },
    });

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [
        MultiplayerService,
        { provide: MercureService, useValue: mercureServiceSpy },
        { provide: NgZone, useValue: ngZoneSpy },
      ],
    });
    service = TestBed.inject(MultiplayerService);
    httpMock = TestBed.inject(HttpTestingController);
    mockMercureService = TestBed.inject(MercureService) as jasmine.SpyObj<MercureService>;
    mockNgZone = TestBed.inject(NgZone) as jasmine.SpyObj<NgZone>;
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should create a room', () => {
    const mockRoom: GameRoom = {
      id: 'room123',
      name: 'Test Room',
      quiz: { id: 1, title: 'Test Quiz', questionCount: 10 },
      creator: { id: 1, username: 'creator' },
      maxPlayers: 4,
      isTeamMode: false,
      status: 'waiting',
      players: [],
      createdAt: Date.now(),
    };

    service.createRoom(1, 4, false, 'Test Room').subscribe(room => {
      expect(room).toEqual(mockRoom);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/multiplayer/room/create`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({
      quizId: 1,
      maxPlayers: 4,
      isTeamMode: false,
      roomName: 'Test Room',
    });
    req.flush(mockRoom);
  });

  it('should join a room', () => {
    const mockRoom: GameRoom = {
      id: 'room123',
      name: 'Test Room',
      quiz: { id: 1, title: 'Test Quiz', questionCount: 10 },
      creator: { id: 1, username: 'creator' },
      maxPlayers: 4,
      isTeamMode: false,
      status: 'waiting',
      players: [
        { id: 1, username: 'creator', isReady: true, isCreator: true },
        { id: 2, username: 'player2', isReady: false, isCreator: false },
      ],
      createdAt: Date.now(),
    };

    service.joinRoom('room123').subscribe(room => {
      expect(room).toEqual(mockRoom);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/multiplayer/room/room123/join`);
    expect(req.request.method).toBe('POST');
    req.flush(mockRoom);
  });

  it('should leave a room', () => {
    const mockRoom: GameRoom = {
      id: 'room123',
      name: 'Test Room',
      quiz: { id: 1, title: 'Test Quiz', questionCount: 10 },
      creator: { id: 1, username: 'creator' },
      maxPlayers: 4,
      isTeamMode: false,
      status: 'waiting',
      players: [{ id: 1, username: 'creator', isReady: true, isCreator: true }],
      createdAt: Date.now(),
    };

    service.leaveRoom('room123').subscribe(room => {
      expect(room).toEqual(mockRoom);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/multiplayer/room/room123/leave`);
    expect(req.request.method).toBe('POST');
    req.flush(mockRoom);
  });

  it('should load available rooms', () => {
    const mockRooms: GameRoom[] = [
      {
        id: 'room1',
        name: 'Room 1',
        quiz: { id: 1, title: 'Quiz 1', questionCount: 5 },
        creator: { id: 1, username: 'user1' },
        maxPlayers: 4,
        isTeamMode: false,
        status: 'waiting',
        players: [],
        createdAt: Date.now(),
      },
    ];

    service.loadAvailableRooms().subscribe(rooms => {
      expect(rooms).toEqual(mockRooms);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/multiplayer/rooms/available`);
    expect(req.request.method).toBe('GET');
    req.flush(mockRooms);
  });

  it('should submit answer in multiplayer game', () => {
    const mockResponse = {
      isCorrect: true,
      points: 10,
      leaderboard: [],
    };

    service.submitAnswer('game123', 1, { answerId: 1 }, 30).subscribe(response => {
      expect(response).toEqual(mockResponse);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/multiplayer/game/game123/answer`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({
      questionId: 1,
      answer: { answerId: 1 },
      timeSpent: 30,
    });
    req.flush(mockResponse);
  });

  it('should get room status', () => {
    const mockRoom: GameRoom = {
      id: 'room123',
      name: 'Test Room',
      quiz: { id: 1, title: 'Test Quiz', questionCount: 10 },
      creator: { id: 1, username: 'creator' },
      maxPlayers: 4,
      isTeamMode: false,
      status: 'playing',
      players: [],
      createdAt: Date.now(),
      gameStartedAt: Date.now(),
      gameId: 'game456',
    };

    service.getRoomStatus('room123').subscribe(room => {
      expect(room).toEqual(mockRoom);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/multiplayer/room/room123`);
    expect(req.request.method).toBe('GET');
    req.flush(mockRoom);
  });

  it('should check if player is in room', () => {
    const room: GameRoom = {
      id: 'room123',
      name: 'Test Room',
      quiz: { id: 1, title: 'Test Quiz', questionCount: 10 },
      creator: { id: 1, username: 'creator' },
      maxPlayers: 4,
      isTeamMode: false,
      status: 'waiting',
      players: [
        { id: 1, username: 'creator', isReady: true, isCreator: true },
        { id: 2, username: 'player2', isReady: false, isCreator: false },
      ],
      createdAt: Date.now(),
    };

    expect(service.isPlayerInRoom(room, 1)).toBe(true);
    expect(service.isPlayerInRoom(room, 2)).toBe(true);
    expect(service.isPlayerInRoom(room, 3)).toBe(false);
  });

  it('should check if user is room creator', () => {
    const room: GameRoom = {
      id: 'room123',
      name: 'Test Room',
      quiz: { id: 1, title: 'Test Quiz', questionCount: 10 },
      creator: { id: 1, username: 'creator' },
      maxPlayers: 4,
      isTeamMode: false,
      status: 'waiting',
      players: [],
      createdAt: Date.now(),
    };

    expect(service.isRoomCreator(room, 1)).toBe(true);
    expect(service.isRoomCreator(room, 2)).toBe(false);
  });
});
