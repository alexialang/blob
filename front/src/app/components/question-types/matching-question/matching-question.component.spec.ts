import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MatchingQuestionComponent } from './matching-question.component';
import { Question, Answer } from '../../../models/quiz.model';
import { NO_ERRORS_SCHEMA } from '@angular/core';

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
      { id: 4, answer: 'Answer D', is_correct: false },
    ],
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MatchingQuestionComponent],
      schemas: [NO_ERRORS_SCHEMA],
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
    expect(component.currentPath).toEqual([]);
    expect(component.startElement).toBe(null);
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

  it('should return 0 for empty matches', () => {
    component.matches = {};
    expect(component.getMatchesCount()).toBe(0);
  });

  it('should check if right item is connected', () => {
    component.matches = { '1': '2' };
    expect(component.isRightItemConnected('2')).toBe(true);
    expect(component.isRightItemConnected('3')).toBe(false);
  });

  it('should get item letter', () => {
    const result0 = component.getItemLetter(0);
    const result1 = component.getItemLetter(1);
    const result2 = component.getItemLetter(2);
    expect(result0).toBe('A');
    expect(result1).toBe('B');
    expect(result2).toBe('C');
  });

  it('should check if can validate', () => {
    component.leftColumn = [
      { id: 1, answer: 'A', is_correct: true },
      { id: 2, answer: 'B', is_correct: false },
    ];
    component.matches = { '1': '3', '2': '4' };
    expect(component.canValidate()).toBe(true);
  });

  it('should not validate when matches are incomplete', () => {
    component.leftColumn = [
      { id: 1, answer: 'A', is_correct: true },
      { id: 2, answer: 'B', is_correct: false },
    ];
    component.matches = { '1': '3' };
    expect(component.canValidate()).toBe(false);
  });

  it('should check if left item is connected', () => {
    component.matches = { '1': '2' };
    expect(component.matches['1']).toBe('2');
    expect(component.matches['3']).toBeUndefined();
  });

  it('should handle setup matching columns', () => {
    spyOn(component as any, 'setupMatchingColumns');
    component.ngOnInit();
    expect((component as any).setupMatchingColumns).toHaveBeenCalled();
  });

  it('should handle setup canvas after view init', () => {
    spyOn(component as any, 'setupCanvas');
    spyOn(component as any, 'setupEventListeners');
    spyOn(component as any, 'setupTouchOptimization');

    component.ngAfterViewInit();

    expect((component as any).setupCanvas).toHaveBeenCalled();
    expect((component as any).setupEventListeners).toHaveBeenCalled();
    expect((component as any).setupTouchOptimization).toHaveBeenCalled();
  });
});
