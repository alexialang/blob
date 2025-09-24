import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { of } from 'rxjs';

import { ConfirmAccountComponent } from './confirm-account.component';

describe('ConfirmAccountComponent', () => {
  let component: ConfirmAccountComponent;
  let fixture: ComponentFixture<ConfirmAccountComponent>;

  beforeEach(async () => {
    const mockActivatedRoute = {
      params: of({}),
      queryParams: of({}),
      data: of({}),
      snapshot: {
        paramMap: {
          get: jasmine.createSpy('get').and.returnValue('test-token'),
        },
        queryParams: {},
        data: {},
      },
    };

    await TestBed.configureTestingModule({
      imports: [ConfirmAccountComponent, HttpClientTestingModule],
      providers: [{ provide: ActivatedRoute, useValue: mockActivatedRoute }],
    }).compileComponents();

    fixture = TestBed.createComponent(ConfirmAccountComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
