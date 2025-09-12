import { TestBed } from '@angular/core/testing';
import { AlertService, AlertMessage } from './alert.service';

describe('AlertService', () => {
  let service: AlertService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(AlertService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should emit success alert with default duration', () => {
    let emittedAlert: AlertMessage | undefined;
    service.alerts$.subscribe(alert => {
      emittedAlert = alert;
    });

    service.success('Test success message');

    expect(emittedAlert).toEqual({
      message: 'Test success message',
      type: 'success',
      duration: 3000
    });
  });

  it('should emit success alert with custom duration', () => {
    let emittedAlert: AlertMessage | undefined;
    service.alerts$.subscribe(alert => {
      emittedAlert = alert;
    });

    service.success('Test success message', 5000);

    expect(emittedAlert).toEqual({
      message: 'Test success message',
      type: 'success',
      duration: 5000
    });
  });

  it('should emit error alert with default duration', () => {
    let emittedAlert: AlertMessage | undefined;
    service.alerts$.subscribe(alert => {
      emittedAlert = alert;
    });

    service.error('Test error message');

    expect(emittedAlert).toEqual({
      message: 'Test error message',
      type: 'error',
      duration: 5000
    });
  });

  it('should emit error alert with custom duration', () => {
    let emittedAlert: AlertMessage | undefined;
    service.alerts$.subscribe(alert => {
      emittedAlert = alert;
    });

    service.error('Test error message', 8000);

    expect(emittedAlert).toEqual({
      message: 'Test error message',
      type: 'error',
      duration: 8000
    });
  });

  it('should emit warning alert with default duration', () => {
    let emittedAlert: AlertMessage | undefined;
    service.alerts$.subscribe(alert => {
      emittedAlert = alert;
    });

    service.warning('Test warning message');

    expect(emittedAlert).toEqual({
      message: 'Test warning message',
      type: 'warning',
      duration: 4000
    });
  });

  it('should emit warning alert with custom duration', () => {
    let emittedAlert: AlertMessage | undefined;
    service.alerts$.subscribe(alert => {
      emittedAlert = alert;
    });

    service.warning('Test warning message', 6000);

    expect(emittedAlert).toEqual({
      message: 'Test warning message',
      type: 'warning',
      duration: 6000
    });
  });

  it('should emit info alert with default duration', () => {
    let emittedAlert: AlertMessage | undefined;
    service.alerts$.subscribe(alert => {
      emittedAlert = alert;
    });

    service.info('Test info message');

    expect(emittedAlert).toEqual({
      message: 'Test info message',
      type: 'info',
      duration: 3000
    });
  });

  it('should emit info alert with custom duration', () => {
    let emittedAlert: AlertMessage | undefined;
    service.alerts$.subscribe(alert => {
      emittedAlert = alert;
    });

    service.info('Test info message', 7000);

    expect(emittedAlert).toEqual({
      message: 'Test info message',
      type: 'info',
      duration: 7000
    });
  });

  it('should emit multiple alerts in sequence', () => {
    const alerts: AlertMessage[] = [];
    service.alerts$.subscribe(alert => {
      alerts.push(alert);
    });

    service.success('First alert');
    service.error('Second alert');
    service.warning('Third alert');
    service.info('Fourth alert');

    expect(alerts.length).toBe(4);
    expect(alerts[0].type).toBe('success');
    expect(alerts[1].type).toBe('error');
    expect(alerts[2].type).toBe('warning');
    expect(alerts[3].type).toBe('info');
  });

  it('should handle empty message', () => {
    let emittedAlert: AlertMessage | undefined;
    service.alerts$.subscribe(alert => {
      emittedAlert = alert;
    });

    service.success('');

    expect(emittedAlert).toEqual({
      message: '',
      type: 'success',
      duration: 3000
    });
  });

  it('should handle zero duration', () => {
    let emittedAlert: AlertMessage | undefined;
    service.alerts$.subscribe(alert => {
      emittedAlert = alert;
    });

    service.success('Test message', 0);

    expect(emittedAlert).toEqual({
      message: 'Test message',
      type: 'success',
      duration: 0
    });
  });

  it('should handle negative duration', () => {
    let emittedAlert: AlertMessage | undefined;
    service.alerts$.subscribe(alert => {
      emittedAlert = alert;
    });

    service.success('Test message', -1000);

    expect(emittedAlert).toEqual({
      message: 'Test message',
      type: 'success',
      duration: -1000
    });
  });
});
