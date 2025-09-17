import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { of } from 'rxjs';

import { QuizGameComponent } from './quiz-game.component';

describe('QuizGameComponent', () => {
  let component: QuizGameComponent;
  let fixture: ComponentFixture<QuizGameComponent>;

  beforeEach(async () => {
    const mockActivatedRoute = {
      params: of({ id: '123' }),
      queryParams: of({}),
      data: of({}),
      snapshot: {
        params: { id: '123' },
        queryParams: {},
        data: {},
      },
    };

    await TestBed.configureTestingModule({
      imports: [QuizGameComponent, HttpClientTestingModule],
      providers: [{ provide: ActivatedRoute, useValue: mockActivatedRoute }],
    }).compileComponents();

    fixture = TestBed.createComponent(QuizGameComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
