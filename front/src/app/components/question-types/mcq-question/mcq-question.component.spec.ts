import { ComponentFixture, TestBed } from '@angular/core/testing';
import { McqQuestionComponent } from './mcq-question.component';
import { Question } from '../../../models/quiz.model';

describe('McqQuestionComponent', () => {
  let component: McqQuestionComponent;
  let fixture: ComponentFixture<McqQuestionComponent>;

  const mockQuestion: Question = {
    id: 1,
    question: 'What is 2 + 2?',
    type_question: 'mcq',
    difficulty: 'easy',
    answers: [
      { id: 1, answer: '3', is_correct: false },
      { id: 2, answer: '4', is_correct: true },
      { id: 3, answer: '5', is_correct: false },
      { id: 4, answer: '6', is_correct: false }
    ]
  };

  const mockProgress = {
    current: 2,
    total: 8,
    percentage: 25
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [McqQuestionComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(McqQuestionComponent);
    component = fixture.componentInstance;
    component.question = mockQuestion;
    component.progress = mockProgress;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    const newComponent = new McqQuestionComponent();
    
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
    
    component.selectAnswer(2);
    
    expect(component.selectedAnswerId).toBe(2);
    expect(component.answerSelected.emit).toHaveBeenCalledWith(2);
  });

  it('should select different answer', () => {
    spyOn(component.answerSelected, 'emit');
    
    component.selectAnswer(3);
    
    expect(component.selectedAnswerId).toBe(3);
    expect(component.answerSelected.emit).toHaveBeenCalledWith(3);
  });

  it('should validate answer when answer is selected', () => {
    spyOn(component.answerValidated, 'emit');
    component.selectedAnswerId = 2;
    
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
    component.selectedAnswerId = 2;
    
    expect(component.isSelected(2)).toBe(true);
    expect(component.isSelected(1)).toBe(false);
    expect(component.isSelected(3)).toBe(false);
  });

  it('should check if no answer is selected', () => {
    component.selectedAnswerId = null;
    
    expect(component.isSelected(1)).toBe(false);
    expect(component.isSelected(2)).toBe(false);
    expect(component.isSelected(3)).toBe(false);
  });

  it('should get flower shape for index', () => {
    expect(component.getFlowerShape(0)).toBe('/assets/svg/blob_flower_color.svg');
    expect(component.getFlowerShape(1)).toBe('/assets/svg/blob_flower_color2.svg');
    expect(component.getFlowerShape(2)).toBe('/assets/svg/blob_flower_color3.svg');
    expect(component.getFlowerShape(3)).toBe('/assets/svg/blob_flower_color1.svg');
  });

  it('should get flower shape for index beyond array length', () => {
    expect(component.getFlowerShape(4)).toBeDefined();
    expect(component.getFlowerShape(5)).toBeDefined();
    expect(component.getFlowerShape(10)).toBeDefined();
  });

  it('should handle single answer selection', () => {
    spyOn(component.answerSelected, 'emit');
    
    component.selectAnswer(1);
    expect(component.selectedAnswerId).toBe(1);
    
    component.selectAnswer(4);
    expect(component.selectedAnswerId).toBe(4);
  });

  it('should handle progress with different values', () => {
    component.progress = {
      current: 5,
      total: 12,
      percentage: 42
    };
    
    expect(component.progress.current).toBe(5);
    expect(component.progress.total).toBe(12);
    expect(component.progress.percentage).toBe(42);
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
      current: 8,
      total: 8,
      percentage: 100
    };
    
    expect(component.progress.current).toBe(8);
    expect(component.progress.total).toBe(8);
    expect(component.progress.percentage).toBe(100);
  });

  it('should handle question with multiple answers', () => {
    expect(component.question.answers.length).toBe(4);
    expect(component.question.answers[0].answer).toBe('3');
    expect(component.question.answers[1].answer).toBe('4');
    expect(component.question.answers[2].answer).toBe('5');
    expect(component.question.answers[3].answer).toBe('6');
  });

  it('should handle question with correct answer', () => {
    const correctAnswer = component.question.answers.find(answer => answer.is_correct);
    
    expect(correctAnswer).toBeDefined();
    expect(correctAnswer?.id).toBe(2);
    expect(correctAnswer?.answer).toBe('4');
  });

  it('should handle flower shape cycling', () => {
    // Test that shapes cycle correctly
    expect(component.getFlowerShape(0)).toBe('/assets/svg/blob_flower_color.svg');
    expect(component.getFlowerShape(4)).toBe('/assets/svg/blob_flower_color.svg');
    expect(component.getFlowerShape(8)).toBe('/assets/svg/blob_flower_color.svg');
    
    expect(component.getFlowerShape(1)).toBe('/assets/svg/blob_flower_color2.svg');
    expect(component.getFlowerShape(5)).toBe('/assets/svg/blob_flower_color2.svg');
    expect(component.getFlowerShape(9)).toBe('/assets/svg/blob_flower_color2.svg');
  });

  it('should emit answer selected with correct answer ID', () => {
    spyOn(component.answerSelected, 'emit');
    
    component.selectAnswer(2);
    
    expect(component.answerSelected.emit).toHaveBeenCalledWith(2);
    expect(component.answerSelected.emit).toHaveBeenCalledTimes(1);
  });

  it('should emit answer validated when answer is selected', () => {
    spyOn(component.answerValidated, 'emit');
    component.selectedAnswerId = 1;
    
    component.validateAnswer();
    
    expect(component.answerValidated.emit).toHaveBeenCalled();
    expect(component.answerValidated.emit).toHaveBeenCalledTimes(1);
  });

  it('should not emit answer validated when no answer is selected', () => {
    spyOn(component.answerValidated, 'emit');
    component.selectedAnswerId = null;
    
    component.validateAnswer();
    
    expect(component.answerValidated.emit).not.toHaveBeenCalled();
  });
});
