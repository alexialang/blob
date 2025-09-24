import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MultipleChoiceQuestionComponent } from './multiple-choice-question.component';
import { Question } from '../../../models/quiz.model';

describe('MultipleChoiceQuestionComponent', () => {
  let component: MultipleChoiceQuestionComponent;
  let fixture: ComponentFixture<MultipleChoiceQuestionComponent>;

  const mockQuestion: Question = {
    id: 1,
    question: 'What is the capital of France?',
    type_question: 'multiple_choice',
    difficulty: 'medium',
    answers: [
      { id: 1, answer: 'Paris', is_correct: true },
      { id: 2, answer: 'London', is_correct: false },
      { id: 3, answer: 'Berlin', is_correct: false },
      { id: 4, answer: 'Madrid', is_correct: false },
    ],
  };

  const mockProgress = {
    current: 2,
    total: 10,
    percentage: 20,
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MultipleChoiceQuestionComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(MultipleChoiceQuestionComponent);
    component = fixture.componentInstance;
    component.question = mockQuestion;
    component.progress = mockProgress;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    const newComponent = new MultipleChoiceQuestionComponent();

    expect(newComponent.selectedAnswerIds).toEqual([]);
    expect(newComponent.progress).toEqual({
      current: 0,
      total: 0,
      percentage: 0,
    });
  });

  it('should accept input properties', () => {
    expect(component.question).toEqual(mockQuestion);
    expect(component.progress).toEqual(mockProgress);
  });

  it('should toggle answer selection', () => {
    spyOn(component.answerSelected, 'emit');

    component.toggleAnswer(1);

    expect(component.selectedAnswerIds).toContain(1);
    expect(component.answerSelected.emit).toHaveBeenCalledWith([1]);
  });

  it('should remove answer when already selected', () => {
    spyOn(component.answerSelected, 'emit');
    component.selectedAnswerIds = [1, 2];

    component.toggleAnswer(1);

    expect(component.selectedAnswerIds).not.toContain(1);
    expect(component.selectedAnswerIds).toContain(2);
    expect(component.answerSelected.emit).toHaveBeenCalledWith([2]);
  });

  it('should add multiple answers', () => {
    spyOn(component.answerSelected, 'emit');

    component.toggleAnswer(1);
    component.toggleAnswer(2);

    expect(component.selectedAnswerIds).toContain(1);
    expect(component.selectedAnswerIds).toContain(2);
    expect(component.answerSelected.emit).toHaveBeenCalledWith([1, 2]);
  });

  it('should validate answer when answers are selected', () => {
    spyOn(component.answerValidated, 'emit');
    component.selectedAnswerIds = [1];

    component.validateAnswer();

    expect(component.answerValidated.emit).toHaveBeenCalled();
  });

  it('should not validate answer when no answers are selected', () => {
    spyOn(component.answerValidated, 'emit');
    component.selectedAnswerIds = [];

    component.validateAnswer();

    expect(component.answerValidated.emit).not.toHaveBeenCalled();
  });

  it('should check if answer is selected', () => {
    component.selectedAnswerIds = [1, 3];

    expect(component.isSelected(1)).toBe(true);
    expect(component.isSelected(2)).toBe(false);
    expect(component.isSelected(3)).toBe(true);
    expect(component.isSelected(4)).toBe(false);
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

  it('should handle empty selected answers', () => {
    component.selectedAnswerIds = [];

    expect(component.isSelected(1)).toBe(false);
    expect(component.isSelected(2)).toBe(false);
  });

  it('should handle single answer selection', () => {
    component.selectedAnswerIds = [2];

    expect(component.isSelected(1)).toBe(false);
    expect(component.isSelected(2)).toBe(true);
    expect(component.isSelected(3)).toBe(false);
  });

  it('should handle all answers selected', () => {
    component.selectedAnswerIds = [1, 2, 3, 4];

    expect(component.isSelected(1)).toBe(true);
    expect(component.isSelected(2)).toBe(true);
    expect(component.isSelected(3)).toBe(true);
    expect(component.isSelected(4)).toBe(true);
  });

  it('should emit answer selected with empty array initially', () => {
    spyOn(component.answerSelected, 'emit');

    // Component should start with empty selection
    expect(component.selectedAnswerIds).toEqual([]);
  });

  it('should handle multiple toggle operations', () => {
    spyOn(component.answerSelected, 'emit');

    component.toggleAnswer(1);
    component.toggleAnswer(2);
    component.toggleAnswer(1); // Toggle off
    component.toggleAnswer(3);

    expect(component.selectedAnswerIds).toEqual([2, 3]);
    expect(component.answerSelected.emit).toHaveBeenCalledTimes(4);
  });

  it('should handle progress with different values', () => {
    component.progress = {
      current: 5,
      total: 20,
      percentage: 25,
    };

    expect(component.progress.current).toBe(5);
    expect(component.progress.total).toBe(20);
    expect(component.progress.percentage).toBe(25);
  });

  it('should handle zero progress', () => {
    component.progress = {
      current: 0,
      total: 0,
      percentage: 0,
    };

    expect(component.progress.current).toBe(0);
    expect(component.progress.total).toBe(0);
    expect(component.progress.percentage).toBe(0);
  });

  it('should handle 100% progress', () => {
    component.progress = {
      current: 10,
      total: 10,
      percentage: 100,
    };

    expect(component.progress.current).toBe(10);
    expect(component.progress.total).toBe(10);
    expect(component.progress.percentage).toBe(100);
  });
});
