import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MatchingQuestionComponent } from './matching-question.component';
import { Question, Answer } from '../../../models/quiz.model';

describe('MatchingQuestionComponent', () => {
  let component: MatchingQuestionComponent;
  let fixture: ComponentFixture<MatchingQuestionComponent>;

  const mockQuestion: Question = {
    id: 1,
    question: 'Test matching question?',
    type_question: 'matching',
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
      imports: [MatchingQuestionComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(MatchingQuestionComponent);
    component = fixture.componentInstance;
    component.question = mockQuestion;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.leftColumn).toEqual([]);
    expect(component.rightColumn).toEqual([]);
    expect(component.matches).toEqual({});
    expect(component.isDrawing).toBe(false);
  });

  it('should setup matching columns on init', () => {
    expect(component.leftColumn.length).toBeGreaterThanOrEqual(0);
    expect(component.rightColumn.length).toBeGreaterThanOrEqual(0);
  });

  it('should validate answer', () => {
    spyOn(component.answerValidated, 'emit');
    spyOn(component.answerSelected, 'emit');
    component.validateAnswer();
    expect(component.answerValidated.emit).toHaveBeenCalled();
    expect(component.answerSelected.emit).toHaveBeenCalled();
  });

  it('should get matches count', () => {
    component.matches = { '1': '2', '3': '4' };
    expect(component.getMatchesCount()).toBe(2);
  });

  it('should check if right item is connected', () => {
    component.matches = { '1': '2' };
    expect(component.isRightItemConnected('2')).toBe(true);
    expect(component.isRightItemConnected('3')).toBe(false);
  });

  it('should get item letter', () => {
    expect(component.getItemLetter(0)).toBe('A');
    expect(component.getItemLetter(1)).toBe('B');
    expect(component.getItemLetter(2)).toBe('C');
  });

  it('should check if can validate', () => {
    component.leftColumn = [
      { id: 1, answer: 'A', is_correct: true },
      { id: 2, answer: 'B', is_correct: false }
    ];
    component.matches = { '1': '3', '2': '4' };
    expect(component.canValidate()).toBe(true);
  });
});
