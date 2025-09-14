import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AlertComponent } from './alert.component';
import { AlertService } from '../../services/alert.service';
import { Subject } from 'rxjs';

describe('AlertComponent', () => {
  let component: AlertComponent;
  let fixture: ComponentFixture<AlertComponent>;
  let alertSubject: Subject<any>;

  beforeEach(async () => {
    alertSubject = new Subject();
    const alertServiceSpy = jasmine.createSpyObj('AlertService', [], {
      alerts$: alertSubject.asObservable()
    });

    await TestBed.configureTestingModule({
      imports: [AlertComponent],
      providers: [
        { provide: AlertService, useValue: alertServiceSpy }
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(AlertComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should display alert when received', () => {
    const mockAlert = { message: 'Test alert', type: 'success' as const };
    alertSubject.next(mockAlert);
    fixture.detectChanges();
    
    expect(component.currentAlert).toEqual(mockAlert);
  });

  it('should close alert when closeAlert is called', () => {
    component.currentAlert = { message: 'Test', type: 'success' as const };
    component.closeAlert();
    
    expect(component.currentAlert).toBeNull();
  });
});
