import { ComponentFixture, TestBed } from '@angular/core/testing';
import { TrueFalseQuestionComponent } from './true-false-question.component';
import { Question } from '../../../models/quiz.model';

describe('TrueFalseQuestionComponent', () => {
  let component: TrueFalseQuestionComponent;
  let fixture: ComponentFixture<TrueFalseQuestionComponent>;

  const mockQuestion: Question = {
    id: 1,
    question: 'Is Paris the capital of France?',
    type_question: 'true_false',
    difficulty: 'easy',
    answers: [
      { id: 1, answer: 'Vrai', is_correct: true },
      { id: 2, answer: 'Faux', is_correct: false }
    ]
  };

  const mockProgress = {
    current: 1,
    total: 5,
    percentage: 20
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TrueFalseQuestionComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(TrueFalseQuestionComponent);
    component = fixture.componentInstance;
    component.question = mockQuestion;
    component.progress = mockProgress;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    const newComponent = new TrueFalseQuestionComponent();
    
    expect(newComponent.selectedAnswerId).toBeNull();
    expect(newComponent.progress).toEqual({
      current: 0,
      total: 0,
      percentage: 0
    });
  });

  it('should accept input properties', () => {
    expect(component.question).toEqual(mockQuestion);
    expect(component.progress).toEqual(mockProgress);
  });

  it('should select answer and emit event', () => {
    spyOn(component.answerSelected, 'emit');
    
    component.selectAnswer(1, true);
    
    expect(component.selectedAnswerId).toBe(1);
    expect(component.answerSelected.emit).toHaveBeenCalledWith(1);
  });

  it('should select different answer', () => {
    spyOn(component.answerSelected, 'emit');
    
    component.selectAnswer(2, false);
    
    expect(component.selectedAnswerId).toBe(2);
    expect(component.answerSelected.emit).toHaveBeenCalledWith(2);
  });

  it('should validate answer when answer is selected', () => {
    spyOn(component.answerValidated, 'emit');
    component.selectedAnswerId = 1;
    
    component.validateAnswer();
    
    expect(component.answerValidated.emit).toHaveBeenCalled();
  });

  it('should not validate answer when no answer is selected', () => {
    spyOn(component.answerValidated, 'emit');
    component.selectedAnswerId = null;
    
    component.validateAnswer();
    
    expect(component.answerValidated.emit).not.toHaveBeenCalled();
  });

  it('should check if answer is selected', () => {
    component.selectedAnswerId = 1;
    
    expect(component.isSelected(1)).toBe(true);
    expect(component.isSelected(2)).toBe(false);
  });

  it('should check if no answer is selected', () => {
    component.selectedAnswerId = null;
    
    expect(component.isSelected(1)).toBe(false);
    expect(component.isSelected(2)).toBe(false);
  });

  it('should get true answer', () => {
    const trueAnswer = component.getTrueAnswer();
    
    expect(trueAnswer).toBeDefined();
    expect(trueAnswer?.id).toBe(1);
    expect(trueAnswer?.answer).toBe('Vrai');
  });

  it('should get false answer', () => {
    const falseAnswer = component.getFalseAnswer();
    
    expect(falseAnswer).toBeDefined();
    expect(falseAnswer?.id).toBe(2);
    expect(falseAnswer?.answer).toBe('Faux');
  });

  it('should handle English true/false answers', () => {
    const englishQuestion: Question = {
      id: 2,
      question: 'Is the sky blue?',
      type_question: 'true_false',
      difficulty: 'easy',
      answers: [
        { id: 3, answer: 'True', is_correct: true },
        { id: 4, answer: 'False', is_correct: false }
      ]
    };
    
    component.question = englishQuestion;
    
    const trueAnswer = component.getTrueAnswer();
    const falseAnswer = component.getFalseAnswer();
    
    expect(trueAnswer?.answer).toBe('True');
    expect(falseAnswer?.answer).toBe('False');
  });

  it('should handle case insensitive answers', () => {
    const mixedCaseQuestion: Question = {
      id: 3,
      question: 'Test question?',
      type_question: 'true_false',
      difficulty: 'easy',
      answers: [
        { id: 5, answer: 'VRAI', is_correct: true },
        { id: 6, answer: 'FAUX', is_correct: false }
      ]
    };
    
    component.question = mixedCaseQuestion;
    
    const trueAnswer = component.getTrueAnswer();
    const falseAnswer = component.getFalseAnswer();
    
    expect(trueAnswer?.answer).toBe('VRAI');
    expect(falseAnswer?.answer).toBe('FAUX');
  });

  it('should handle empty answers array', () => {
    const emptyQuestion: Question = {
      id: 4,
      question: 'Test question?',
      type_question: 'true_false',
      difficulty: 'easy',
      answers: []
    };
    
    component.question = emptyQuestion;
    
    const trueAnswer = component.getTrueAnswer();
    const falseAnswer = component.getFalseAnswer();
    
    expect(trueAnswer).toBeUndefined();
    expect(falseAnswer).toBeUndefined();
  });

  it('should handle single answer selection', () => {
    spyOn(component.answerSelected, 'emit');
    
    component.selectAnswer(1, true);
    expect(component.selectedAnswerId).toBe(1);
    
    component.selectAnswer(2, false);
    expect(component.selectedAnswerId).toBe(2);
  });

  it('should handle progress with different values', () => {
    component.progress = {
      current: 3,
      total: 10,
      percentage: 30
    };
    
    expect(component.progress.current).toBe(3);
    expect(component.progress.total).toBe(10);
    expect(component.progress.percentage).toBe(30);
  });

  it('should handle zero progress', () => {
    component.progress = {
      current: 0,
      total: 0,
      percentage: 0
    };
    
    expect(component.progress.current).toBe(0);
    expect(component.progress.total).toBe(0);
    expect(component.progress.percentage).toBe(0);
  });

  it('should handle 100% progress', () => {
    component.progress = {
      current: 5,
      total: 5,
      percentage: 100
    };
    
    expect(component.progress.current).toBe(5);
    expect(component.progress.total).toBe(5);
    expect(component.progress.percentage).toBe(100);
  });

  it('should handle answers with special characters', () => {
    const specialQuestion: Question = {
      id: 5,
      question: 'Test question?',
      type_question: 'true_false',
      difficulty: 'easy',
      answers: [
        { id: 7, answer: 'Vrai (correct)', is_correct: true },
        { id: 8, answer: 'Faux (incorrect)', is_correct: false }
      ]
    };
    
    component.question = specialQuestion;
    
    const trueAnswer = component.getTrueAnswer();
    const falseAnswer = component.getFalseAnswer();
    
    expect(trueAnswer?.answer).toBe('Vrai (correct)');
    expect(falseAnswer?.answer).toBe('Faux (incorrect)');
  });
});

