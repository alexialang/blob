import { TestBed } from '@angular/core/testing';
import { GameTimerService } from './game-timer.service';
import { fakeAsync, tick } from '@angular/core/testing';

describe('GameTimerService', () => {
  let service: GameTimerService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(GameTimerService);
  });

  afterEach(() => {
    service.destroy();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should initialize with default values', () => {
    service.getTimeLeft().subscribe(time => expect(time).toBe(0));
    service.getPhase().subscribe(phase => expect(phase).toBe('waiting'));
    service.isRunning().subscribe(running => expect(running).toBeFalsy());
    expect(service.getCurrentTime()).toBe(0);
    expect(service.getCurrentPhase()).toBe('waiting');
    expect(service.isTimerRunning()).toBeFalsy();
  });

  it('should start local timer correctly', fakeAsync(() => {
    const duration = 10;
    const phase = 'question';

    service.startLocalTimer(duration, phase);

    expect(service.getCurrentTime()).toBe(duration);
    expect(service.getCurrentPhase()).toBe(phase);
    expect(service.isTimerRunning()).toBeTruthy();

    // Simuler le passage du temps
    tick(2000);
    expect(service.getCurrentTime()).toBeLessThan(duration);

    // Arrêter le timer
    service.stopTimer();
    expect(service.getCurrentTime()).toBe(0);
    expect(service.isTimerRunning()).toBeFalsy();
  }));

  it('should start synchronized timer correctly', fakeAsync(() => {
    const serverTimestamp = Date.now() - 5000; // 5 secondes dans le passé
    const duration = 30;
    const phase = 'question';

    service.startSynchronizedTimer(serverTimestamp, duration, phase);

    // Le temps restant devrait être inférieur à la durée totale
    expect(service.getCurrentTime()).toBeLessThanOrEqual(duration);
    expect(service.getCurrentPhase()).toBe(phase);
    expect(service.isTimerRunning()).toBeTruthy();

    tick(1000);
    // Le temps devrait diminuer
    const timeAfterTick = service.getCurrentTime();
    expect(timeAfterTick).toBeLessThan(duration);
  }));

  it('should update from server correctly', () => {
    const serverTimeLeft = 15;
    const phase = 'answer';

    service.updateFromServer(serverTimeLeft, phase);

    expect(service.getCurrentTime()).toBe(serverTimeLeft);
    expect(service.getCurrentPhase()).toBe(phase);
  });

  it('should stop timer when server time is zero', () => {
    const serverTimeLeft = 0;
    const phase = 'finished';

    service.updateFromServer(serverTimeLeft, phase);

    expect(service.getCurrentTime()).toBe(0);
    expect(service.getCurrentPhase()).toBe(phase);
    expect(service.isTimerRunning()).toBeFalsy();
  });

  it('should handle negative server time', () => {
    const serverTimeLeft = -5;
    const phase = 'finished';

    service.updateFromServer(serverTimeLeft, phase);

    expect(service.getCurrentTime()).toBe(0);
    expect(service.getCurrentPhase()).toBe(phase);
  });

  it('should stop timer correctly', () => {
    service.startLocalTimer(30, 'question');
    expect(service.isTimerRunning()).toBeTruthy();

    service.stopTimer();

    expect(service.getCurrentTime()).toBe(0);
    expect(service.isTimerRunning()).toBeFalsy();
  });

  it('should handle multiple timer starts', () => {
    service.startLocalTimer(30, 'question');
    expect(service.getCurrentTime()).toBe(30);

    service.startLocalTimer(60, 'answer');
    expect(service.getCurrentTime()).toBe(60);
    expect(service.getCurrentPhase()).toBe('answer');
  });

  it('should emit observable values correctly', () => {
    let timeLeftValues: number[] = [];
    let phaseValues: string[] = [];
    let runningValues: boolean[] = [];

    service.getTimeLeft().subscribe(time => timeLeftValues.push(time));
    service.getPhase().subscribe(phase => phaseValues.push(phase));
    service.isRunning().subscribe(running => runningValues.push(running));

    service.startLocalTimer(10, 'question');

    expect(timeLeftValues).toContain(0); // Valeur initiale
    expect(timeLeftValues).toContain(10); // Après démarrage
    expect(phaseValues).toContain('waiting'); // Valeur initiale
    expect(phaseValues).toContain('question'); // Après démarrage
    expect(runningValues).toContain(false); // Valeur initiale
    expect(runningValues).toContain(true); // Après démarrage
  });

  it('should handle timer expiration', fakeAsync(() => {
    const duration = 1; // 1 seconde
    const phase = 'question';

    service.startLocalTimer(duration, phase);

    // Attendre que le timer expire
    tick(2000);

    expect(service.getCurrentTime()).toBe(0);
    expect(service.isTimerRunning()).toBeFalsy();
  }));

  it('should destroy correctly', () => {
    service.startLocalTimer(30, 'question');
    expect(service.isTimerRunning()).toBeTruthy();

    service.destroy();

    expect(service.getCurrentTime()).toBe(0);
    expect(service.isTimerRunning()).toBeFalsy();
  });

  it('should handle synchronized timer with future server timestamp', () => {
    const serverTimestamp = Date.now() + 1000; // 1 seconde dans le futur
    const duration = 30;
    const phase = 'question';

    service.startSynchronizedTimer(serverTimestamp, duration, phase);

    // Le temps restant devrait être proche de la durée totale (avec une marge pour les calculs)
    expect(service.getCurrentTime()).toBeGreaterThan(duration - 2);
    expect(service.getCurrentTime()).toBeLessThanOrEqual(duration + 2);
  });

  it('should handle synchronized timer with old server timestamp', () => {
    const serverTimestamp = Date.now() - 35000; // 35 secondes dans le passé
    const duration = 30;
    const phase = 'question';

    service.startSynchronizedTimer(serverTimestamp, duration, phase);

    // Le temps devrait être expiré
    expect(service.getCurrentTime()).toBe(0);
  });
});
