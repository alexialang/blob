import { ComponentFixture, TestBed } from '@angular/core/testing';
import { BlindTestQuestionComponent } from './blind-test-question.component';
import { NO_ERRORS_SCHEMA } from '@angular/core';

describe('BlindTestQuestionComponent', () => {
  let component: BlindTestQuestionComponent;
  let fixture: ComponentFixture<BlindTestQuestionComponent>;

  const mockQuestion = {
    id: 1,
    question: 'Quel est le titre de cette chanson ?',
    type_question: 'blind_test',
    difficulty: 'easy',
    answers: [
      { id: 1, answer: 'Chanson A', is_correct: true },
      { id: 2, answer: 'Chanson B', is_correct: false },
      { id: 3, answer: 'Chanson C', is_correct: false }
    ]
  } as any;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [BlindTestQuestionComponent],
      schemas: [NO_ERRORS_SCHEMA]
    }).compileComponents();

    fixture = TestBed.createComponent(BlindTestQuestionComponent);
    component = fixture.componentInstance;
    component.question = mockQuestion;
    component.progress = { current: 1, total: 5, percentage: 20 };
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component.selectedAnswerId).toBe(null);
    expect(component.isPlaying).toBe(false);
    expect(component.currentTime).toBe(0);
    expect(component.duration).toBe(30);
  });

  it('should shuffle answers on init', () => {
    component.ngOnInit();
    expect(component.shuffledAnswers.length).toBe(3);
    expect(component.shuffledAnswers).toContain(mockQuestion.answers[0]);
    expect(component.shuffledAnswers).toContain(mockQuestion.answers[1]);
    expect(component.shuffledAnswers).toContain(mockQuestion.answers[2]);
  });

  it('should select answer', () => {
    spyOn(component.answerSelected, 'emit');
    const answerId = 1;
    
    component.selectAnswer(answerId);
    
    expect(component.selectedAnswerId).toBe(answerId);
    expect(component.answerSelected.emit).toHaveBeenCalledWith(answerId);
  });

  it('should validate answer', () => {
    spyOn(component.answerValidated, 'emit');
    component.selectedAnswerId = 1;
    
    component.validateAnswer();
    
    expect(component.answerValidated.emit).toHaveBeenCalled();
  });

  it('should not validate answer if none selected', () => {
    spyOn(component.answerValidated, 'emit');
    component.selectedAnswerId = null;
    
    component.validateAnswer();
    
    expect(component.answerValidated.emit).not.toHaveBeenCalled();
  });

  it('should toggle play/pause', () => {
    component.isPlaying = false;
    
    component.togglePlayPause();
    expect(component.isPlaying).toBe(true);
    
    component.togglePlayPause();
    expect(component.isPlaying).toBe(false);
  });

  it('should clear interval on destroy', () => {
    spyOn(window, 'clearInterval');
    component.intervalId = 123;
    
    component.ngOnDestroy();
    
    expect(window.clearInterval).toHaveBeenCalledWith(123);
  });

  it('should not clear interval if none exists', () => {
    spyOn(window, 'clearInterval');
    component.intervalId = null;
    
    component.ngOnDestroy();
    
    expect(window.clearInterval).not.toHaveBeenCalled();
  });
});