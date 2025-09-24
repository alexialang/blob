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
    expect(service.getCurrentTime()).toBe(0);
    expect(service.getCurrentPhase()).toBe('waiting');
    expect(service.isTimerRunning()).toBe(false);
  });

  it('should start local timer correctly', fakeAsync(() => {
    const duration = 5;
    const phase = 'playing';

    service.startLocalTimer(duration, phase);

    expect(service.getCurrentTime()).toBe(duration);
    expect(service.getCurrentPhase()).toBe(phase);
    expect(service.isTimerRunning()).toBe(true);

    // Avancer le temps de 1 seconde
    tick(1000);
    expect(service.getCurrentTime()).toBe(duration - 1);

    // Avancer le temps jusqu'à la fin
    tick(4000);
    expect(service.getCurrentTime()).toBe(0);
    expect(service.isTimerRunning()).toBe(false);
  }));

  it('should start synchronized timer correctly', fakeAsync(() => {
    const serverTimestamp = Date.now() - 2000; // 2 secondes dans le passé
    const duration = 10;
    const phase = 'synchronized';

    service.startSynchronizedTimer(serverTimestamp, duration, phase);

    expect(service.getCurrentTime()).toBe(duration - 2); // 10 - 2 = 8
    expect(service.getCurrentPhase()).toBe(phase);
    expect(service.isTimerRunning()).toBe(true);

    // Avancer le temps de 1 seconde
    tick(1000);
    expect(service.getCurrentTime()).toBe(duration - 3); // 10 - 3 = 7
  }));

  it('should update from server correctly', () => {
    service.updateFromServer(15, 'server-update');

    expect(service.getCurrentTime()).toBe(15);
    expect(service.getCurrentPhase()).toBe('server-update');
  });

  it('should stop timer when server time is 0 or negative', () => {
    service.startLocalTimer(10, 'playing');
    expect(service.isTimerRunning()).toBe(true);

    service.updateFromServer(0, 'finished');
    expect(service.getCurrentTime()).toBe(0);
    expect(service.isTimerRunning()).toBe(false);
  });

  it('should stop timer correctly', () => {
    service.startLocalTimer(10, 'playing');
    expect(service.isTimerRunning()).toBe(true);

    service.stopTimer();
    expect(service.getCurrentTime()).toBe(0);
    expect(service.isTimerRunning()).toBe(false);
  });

  it('should handle multiple timer starts correctly', fakeAsync(() => {
    // Premier timer
    service.startLocalTimer(5, 'first');
    expect(service.getCurrentTime()).toBe(5);

    // Deuxième timer (doit arrêter le premier)
    service.startLocalTimer(10, 'second');
    expect(service.getCurrentTime()).toBe(10);
    expect(service.getCurrentPhase()).toBe('second');

    tick(1000);
    expect(service.getCurrentTime()).toBe(9);
  }));

  it('should handle negative time correctly', () => {
    const serverTimestamp = Date.now() - 5000; // 5 secondes dans le passé
    const duration = 3; // Plus court que le temps écoulé

    service.startSynchronizedTimer(serverTimestamp, duration, 'negative-test');
    expect(service.getCurrentTime()).toBe(0); // Doit être 0, pas négatif
  });

  it('should emit time updates through observable', () => {
    let lastValue = 0;

    service.getTimeLeft().subscribe((time: number) => {
      lastValue = time;
    });

    // L'état initial est 0
    expect(lastValue).toBe(0);

    service.startLocalTimer(5, 'observable-test');
    expect(lastValue).toBe(5);
  });

  it('should emit phase updates through observable', () => {
    const phaseValues: string[] = [];
    service.getPhase().subscribe((phase: string) => {
      phaseValues.push(phase);
    });

    service.startLocalTimer(5, 'playing');
    service.updateFromServer(3, 'finished');

    expect(phaseValues).toEqual(['waiting', 'playing', 'finished']);
  });

  it('should emit running state updates through observable', () => {
    let lastValue = false;

    service.isRunning().subscribe((running: boolean) => {
      lastValue = running;
    });

    // L'état initial est false
    expect(lastValue).toBe(false);

    service.startLocalTimer(5, 'running-test');
    // Maintenant c'est true
    expect(lastValue).toBe(true);
  });

  it('should destroy timer on destroy', () => {
    service.startLocalTimer(10, 'destroy-test');
    expect(service.isTimerRunning()).toBe(true);

    service.destroy();
    expect(service.isTimerRunning()).toBe(false);
    expect(service.getCurrentTime()).toBe(0);
  });

  it('should handle timer completion', fakeAsync(() => {
    let completed = false;
    service.getTimeLeft().subscribe((time: number) => {
      if (time === 0) {
        completed = true;
      }
    });

    service.startLocalTimer(1, 'completion-test');
    tick(1000);

    expect(completed).toBe(true);
    expect(service.getCurrentTime()).toBe(0);
  }));

  it('should handle multiple timer starts', () => {
    service.startLocalTimer(5, 'test1');
    expect(service.getCurrentTime()).toBe(5);

    service.startLocalTimer(10, 'test2');
    expect(service.getCurrentTime()).toBe(10);
  });

  it('should handle server update with different phase', () => {
    service.startLocalTimer(5, 'playing');
    expect(service.getCurrentPhase()).toBe('playing');

    service.updateFromServer(3, 'finished');
    expect(service.getCurrentTime()).toBe(3);
    expect(service.getCurrentPhase()).toBe('finished');
  });

  it('should handle stop timer when not running', () => {
    expect(service.isTimerRunning()).toBe(false);
    service.stopTimer();
    expect(service.isTimerRunning()).toBe(false);
  });
});
