import { ComponentFixture, TestBed } from '@angular/core/testing';
import { IntruderQuestionComponent } from './intruder-question.component';
import { Question, Answer } from '../../../models/quiz.model';

describe('IntruderQuestionComponent', () => {
  let component: IntruderQuestionComponent;
  let fixture: ComponentFixture<IntruderQuestionComponent>;

  const mockQuestion: Question = {
    id: 1,
    question: 'Test intruder question?',
    type_question: 'intruder',
    difficulty: 'easy',
    answers: [
      { id: 1, answer: 'Answer A', is_correct: true },
      { id: 2, answer: 'Answer B', is_correct: false },
      { id: 3, answer: 'Answer C', is_correct: false },
      { id: 4, answer: 'Answer D', is_correct: false }
    ]
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [IntruderQuestionComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(IntruderQuestionComponent);
    component = fixture.componentInstance;
    component.question = mockQuestion;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.selectedAnswerId).toBeNull();
    expect(component.hoveredCard).toBeNull();
    expect(component.isAccused).toBe(false);
  });

  it('should shuffle answers on init', () => {
    expect(component.shuffledAnswers.length).toBe(3);
    expect(component.shuffledAnswers).toEqual(jasmine.arrayContaining(mockQuestion.answers.slice(0, 3)));
  });

  it('should investigate suspect', () => {
    spyOn(component.answerSelected, 'emit');
    component.investigateSuspect(1, 0);
    expect(component.selectedAnswerId).toBe(1);
    expect(component.answerSelected.emit).toHaveBeenCalledWith(1);
  });

  it('should make accusation when answer selected', () => {
    spyOn(component.answerValidated, 'emit');
    component.selectedAnswerId = 1;
    component.makeAccusation();
    expect(component.isAccused).toBe(true);
    expect(component.answerValidated.emit).toHaveBeenCalled();
  });

  it('should not make accusation when no answer selected', () => {
    spyOn(component.answerValidated, 'emit');
    component.selectedAnswerId = null;
    component.makeAccusation();
    expect(component.isAccused).toBe(false);
    expect(component.answerValidated.emit).not.toHaveBeenCalled();
  });

  it('should check if answer is selected', () => {
    component.selectedAnswerId = 1;
    expect(component.isSelected(1)).toBe(true);
    expect(component.isSelected(2)).toBe(false);
  });

  it('should get avatar svg', () => {
    expect(component.getAvatarSvg(0)).toBe('/assets/avatars/blob_flower_blue.svg');
    expect(component.getAvatarSvg(1)).toBe('/assets/avatars/blob_circle.svg');
    expect(component.getAvatarSvg(2)).toBe('/assets/avatars/blob_pic_orange.svg');
  });

  it('should get validate text', () => {
    expect(component.getValidateText()).toBe('SÉLECTIONNER');
    component.selectedAnswerId = 1;
    expect(component.getValidateText()).toBe('VALIDER');
    component.isAccused = true;
    expect(component.getValidateText()).toBe('VALIDÉ!');
  });
});
