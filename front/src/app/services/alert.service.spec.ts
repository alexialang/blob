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

  it('should show success alert', (done) => {
    const message = 'Success message';
    
    service.alerts$.subscribe(alert => {
      expect(alert.message).toBe(message);
      expect(alert.type).toBe('success');
      done();
    });
    
    service.success(message);
  });

  it('should show error alert', (done) => {
    const message = 'Error message';
    
    service.alerts$.subscribe(alert => {
      expect(alert.message).toBe(message);
      expect(alert.type).toBe('error');
      done();
    });
    
    service.error(message);
  });

  it('should show warning alert', (done) => {
    const message = 'Warning message';
    
    service.alerts$.subscribe(alert => {
      expect(alert.message).toBe(message);
      expect(alert.type).toBe('warning');
      done();
    });
    
    service.warning(message);
  });

  it('should show info alert', (done) => {
    const message = 'Info message';
    
    service.alerts$.subscribe(alert => {
      expect(alert.message).toBe(message);
      expect(alert.type).toBe('info');
      done();
    });
    
    service.info(message);
  });

  it('should show alert with custom duration', (done) => {
    const message = 'Custom duration message';
    const duration = 10000;
    
    service.alerts$.subscribe(alert => {
      expect(alert.message).toBe(message);
      expect(alert.duration).toBe(duration);
      done();
    });
    
    service.success(message, duration);
  });

  it('should show alert with default duration', (done) => {
    const message = 'Default duration message';
    
    service.alerts$.subscribe(alert => {
      expect(alert.message).toBe(message);
      expect(alert.duration).toBe(3000); // Default duration for success
      done();
    });
    
    service.success(message);
  });
});
