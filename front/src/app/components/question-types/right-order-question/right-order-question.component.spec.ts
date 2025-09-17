import { ComponentFixture, TestBed } from '@angular/core/testing';
import { RightOrderQuestionComponent } from './right-order-question.component';
import { Question, Answer } from '../../../models/quiz.model';

describe('RightOrderQuestionComponent', () => {
  let component: RightOrderQuestionComponent;
  let fixture: ComponentFixture<RightOrderQuestionComponent>;

  const mockQuestion: Question = {
    id: 1,
    question: 'Test right order question?',
    type_question: 'right_order',
    difficulty: 'easy',
    answers: [
      { id: 1, answer: 'Answer A', is_correct: true },
      { id: 2, answer: 'Answer B', is_correct: false },
      { id: 3, answer: 'Answer C', is_correct: false },
      { id: 4, answer: 'Answer D', is_correct: false },
    ],
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [RightOrderQuestionComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(RightOrderQuestionComponent);
    component = fixture.componentInstance;
    component.question = mockQuestion;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.orderedAnswers).toBeDefined();
    expect(component.orderedAnswers.length).toBe(4);
  });

  it('should shuffle answers on init', () => {
    expect(component.orderedAnswers.length).toBe(4);
    expect(component.orderedAnswers).toEqual(jasmine.arrayContaining(mockQuestion.answers));
  });

  it('should validate answer', () => {
    spyOn(component.answerValidated, 'emit');
    component.validateAnswer();
    expect(component.answerValidated.emit).toHaveBeenCalled();
  });

  it('should reset order', () => {
    spyOn(component.answerSelected, 'emit');
    component.resetOrder();
    expect(component.answerSelected.emit).toHaveBeenCalledWith([]);
    expect(component.orderedAnswers.length).toBe(4);
  });

  it('should emit answer selected on drop', () => {
    spyOn(component.answerSelected, 'emit');
    const mockEvent = {
      previousIndex: 0,
      currentIndex: 1,
    } as any;
    component.drop(mockEvent);
    expect(component.answerSelected.emit).toHaveBeenCalled();
  });
});
