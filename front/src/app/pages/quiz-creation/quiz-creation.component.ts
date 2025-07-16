import { Component, OnInit, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  FormBuilder,
  FormGroup,
  FormArray,
  Validators,
  ReactiveFormsModule,
  FormsModule,
  AbstractControl
} from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { QuizManagementService } from '../../services/quiz-management.service';

interface TypeQuestion {
  id: number;
  name: string;
  key: string;
}

interface Category {
  id: number;
  name: string;
}

interface Group {
  id: number;
  name: string;
}

interface Status {
  id: number;
  name: string;
  value: string;
}

@Component({
  selector: 'app-quiz-creation',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule],
  templateUrl: './quiz-creation.component.html',
  styleUrls: ['./quiz-creation.component.scss']
})
export class QuizCreationComponent implements OnInit {
  quizForm!: FormGroup;
  typeQuestions: TypeQuestion[] = [];
  categories: Category[] = [];
  filteredCategories: Category[] = [];
  groups: Group[] = [];
  statuses: Status[] = [];
  isSubmitting = false;
  isEditMode = false;
  quizId: number | null = null;

  categorySearch = '';
  showCategoryDropdown = false;
  selectedCategory: Category | null = null;

  constructor(
    private readonly fb: FormBuilder,
    private readonly router: Router,
    private readonly route: ActivatedRoute,
    private readonly quizService: QuizManagementService
  ) {}

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: Event): void {
    const target = event.target as any;
    if (!target?.closest('.category-search')) {
      this.showCategoryDropdown = false;
    }
  }

  ngOnInit(): void {
    this.quizForm = this.createQuizForm();
    this.loadTypeQuestions();
    this.loadCategories();
    this.loadStatuses();

    this.quizForm.get('is_public')?.valueChanges.subscribe(isPublic => {
      if (isPublic === false) {
        this.loadGroups();
      }
    });

    this.route.params.subscribe(params => {
      if (params['id']) {
        this.isEditMode = true;
        this.quizId = +params['id'];
        this.loadQuizForEdit(this.quizId);
      }
    });
  }

  private createQuizForm(): FormGroup {
    const form = this.fb.group({
      title: ['', [Validators.required, Validators.minLength(3)]],
      description: ['', [Validators.required, Validators.minLength(10)]],
      status: ['draft', Validators.required],
      is_public: [true, Validators.required],
      category: [null, Validators.required],
      groups: [[]],
      questions: this.fb.array([])
    });

    setTimeout(() => this.addQuestion(), 0);
    return form;
  }

  get questions(): FormArray {
    return this.quizForm.get('questions') as FormArray;
  }

  addQuestion(): void {
    const questionForm = this.fb.group({
      question: ['', [Validators.required, Validators.minLength(5)]],
      type_question: [null, Validators.required],
      answers: this.fb.array([])
    });

    this.questions.push(questionForm);
    this.addAnswer(this.questions.length - 1);
    this.addAnswer(this.questions.length - 1);
  }

  removeQuestion(index: number): void {
    if (this.questions.length > 1) {
      this.questions.removeAt(index);
    }
  }

  getAnswers(questionIndex: number): FormArray {
    return this.questions.at(questionIndex).get('answers') as FormArray;
  }

  addAnswer(questionIndex: number): void {
    const answerForm = this.fb.group({
      answer: ['', Validators.required],
      is_correct: [false],
      order_correct: [''],
      pair_id: [''],
      is_intrus: [false]
    });

    this.getAnswers(questionIndex).push(answerForm);
  }

  removeAnswer(questionIndex: number, answerIndex: number): void {
    const answers = this.getAnswers(questionIndex);
    if (answers.length > 2) {
      answers.removeAt(answerIndex);
    }
  }

  onCorrectAnswerChange(questionIndex: number, answerIndex: number): void {
    const answers = this.getAnswers(questionIndex);
    const questionType = this.questions.at(questionIndex).get('type_question')?.value;

    if (questionType === 'MCQ') {
      for (let i = 0; i < answers.length; i++) {
        if (i !== answerIndex) {
          answers.at(i).get('is_correct')?.setValue(false);
        }
      }
    } else if (questionType === 'find_the_intruder') {
      const currentAnswer = answers.at(answerIndex);
      const isIntrusValue = currentAnswer.get('is_intrus')?.value;
      if (isIntrusValue) {
        for (let i = 0; i < answers.length; i++) {
          if (i !== answerIndex) {
            answers.at(i).get('is_intrus')?.setValue(false);
          }
        }
      }
    }
  }

  onQuestionTypeChange(questionIndex: number): void {
    const questionControl = this.questions.at(questionIndex);
    const questionType = questionControl.get('type_question')?.value;
    if (!questionType) return;

    const answers = this.getAnswers(questionIndex);
    while (answers.length > 0) {
      answers.removeAt(0);
    }

        this.initializeAnswersForType(questionIndex, questionType);
    }

    private initializeAnswersForType(questionIndex: number, questionType: string): void {
        const minAnswers = this.getMinAnswersForType(questionType);

    if (questionType === 'matching') {
      for (let i = 0; i < minAnswers; i++) {
        this.addAnswer(questionIndex);
      }
      this.initializeMatchingPairs(questionIndex);
    } else {
      for (let i = 0; i < minAnswers; i++) {
        this.addAnswer(questionIndex);
      }
    }
  }

  private initializeMatchingPairs(questionIndex: number): void {
    const answers = this.getAnswers(questionIndex);
    const alreadyPaired = answers.controls.some((control: AbstractControl) => !!control.get('pair_id')?.value);
    if (alreadyPaired) return;

    for (let i = 0; i < answers.length; i++) {
      const pairNumber = Math.floor(i / 2) + 1;
      const side = i % 2 === 0 ? 'left' : 'right';
      const pairId = `${side}_${pairNumber}`;
      answers.at(i).get('pair_id')?.setValue(pairId);
    }
  }

  private getMinAnswersForType(questionType: string): number {
    switch (questionType) {
      case 'MCQ':
      case 'multiple_choice': return 3;
      case 'right_order': return 3;
      case 'matching':
      case 'find_the_intruder': return 4;
      case 'blind_test': return 1;
      default: return 2;
    }
  }

  getTypeQuestionKeys(): string {
    return this.typeQuestions.map(t => t.key).join(', ');
  }

  getQuestionTypeLabel(type: string): string {
    switch (type) {
      case 'MCQ': return 'QCM';
      case 'multiple_choice': return 'Choix multiple';
      case 'right_order': return 'Remise dans le bon ordre';
      case 'matching': return 'Association d\'éléments';
      case 'find_the_intruder': return 'Intrus';
      case 'blind_test': return 'Blind Test';
      default: return type;
    }
  }

  getMinAnswersForTypePublic(type: string): number {
    return this.getMinAnswersForType(type);
  }

  getMatchingPairs(questionIndex: number): string[] {
    const answers = this.getAnswers(questionIndex);
    const pairIds: Set<string> = new Set();

    answers.controls.forEach(control => {
      const pair = control.get('pair_id')?.value;
      if (pair?.startsWith('left_')) {
        const index = pair.split('_')[1];
        if (index) pairIds.add(index);
      }
    });

    return Array.from(pairIds);
  }

  getAnswerControlByPairId(questionIndex: number, pairId: string): any {
    return this.getAnswers(questionIndex).controls.find(ctrl => ctrl.get('pair_id')?.value === pairId)?.get('answer');
  }

  filterCategories(): void {
    const search = this.categorySearch.toLowerCase();
    this.filteredCategories = this.categories.filter(cat =>
      cat.name.toLowerCase().includes(search)
    );
  }

  selectCategory(cat: Category): void {
    this.quizForm.get('category')?.setValue(cat.id);
    this.selectedCategory = cat;
    this.categorySearch = cat.name;
    this.showCategoryDropdown = false;
  }

  clearCategory(): void {
    this.quizForm.get('category')?.reset();
    this.selectedCategory = null;
    this.categorySearch = '';
  }

  loadTypeQuestions(): void {
    this.quizService.getTypeQuestions().subscribe(res => {
      this.typeQuestions = res;
    });
  }

  loadCategories(): void {
    this.quizService.getCategories().subscribe(res => {
      this.categories = res;
      this.filteredCategories = res;
    });
  }

  loadGroups(): void {
    this.quizService.getGroups().subscribe(res => {
      this.groups = res;
    });
  }

  loadStatuses(): void {
    this.quizService.getStatuses().subscribe(res => {
      this.statuses = res;
    });
  }



  cancel(): void {
    this.router.navigate(['/quiz-management']);
  }

  loadQuizForEdit(id: number): void {
    this.quizService.getQuiz(id).subscribe(res => {

      this.quizForm.patchValue({
        title: res.title,
        description: res.description,
        status: res.status,
        is_public: res.is_public,
        category: res.category?.id || null,
        groups: res.groups.map((g: any) => g.id)
      });

      if (res.category) {
        const found = this.categories.find(c => c.id === res.category.id);
        if (found) {
          this.selectedCategory = found;
          this.categorySearch = found.name;
        }
      }

      const questionsArray = this.quizForm.get('questions') as FormArray;
      while (questionsArray.length) {
        questionsArray.removeAt(0);
      }

      res.questions.forEach((q: any) => {
        let questionTypeKey = '';
        if (typeof q.type_question === 'object' && q.type_question !== null) {
          questionTypeKey = q.type_question.key || q.type_question.name || '';
        } else if (typeof q.type_question === 'string') {
          questionTypeKey = q.type_question;
        }


        const questionForm = this.fb.group({
          question: [q.question || '', [Validators.required, Validators.minLength(5)]],
          type_question: [questionTypeKey, Validators.required],
          answers: this.fb.array([])
        });

        questionsArray.push(questionForm);
        const questionIndex = questionsArray.length - 1;

        if (q.answers && q.answers.length) {
          q.answers.forEach((ans: any) => {
            const answerForm = this.fb.group({
              answer: [ans.answer || '', Validators.required],
              is_correct: [ans.is_correct || false],
              order_correct: [ans.order_correct ?? '', Validators.required],
              pair_id: [ans.pair_id || ''],
              is_intrus: [ans.is_intrus || false]
            });
            (questionForm.get('answers') as FormArray).push(answerForm);
          });
        } else {
          this.initializeAnswersForType(questionIndex, questionTypeKey);
        }
      });

    });
  }

  onSubmit(): void {
    if (this.quizForm.invalid) return;

    this.isSubmitting = true;
    const formValue = this.quizForm.value;

    const payload = {
      ...formValue,
      questions: formValue.questions.map((q: any) => ({
        ...q,
        answers: q.answers.map((a: any) => ({
          ...a,
          order_correct: a.order_correct || null
        }))
      }))
    };

    const action = this.isEditMode
      ? this.quizService.updateQuiz(this.quizId!, payload)
      : this.quizService.createQuiz(payload);

    action.subscribe({
      next: () => {
        this.isSubmitting = false;
        this.router.navigate(['/quiz-management']);
      },
      error: () => {
        this.isSubmitting = false;
      }
    });
  }

  trackByKey(index: number, item: TypeQuestion): string {
    return item.key;
  }

  addMatchingPair(questionIndex: number): void {
    const answers = this.getAnswers(questionIndex);
    const currentPairs = this.getMatchingPairs(questionIndex);
    const nextPairNumber = currentPairs.length + 1;

    const leftAnswerForm = this.fb.group({
      answer: ['', Validators.required],
      is_correct: [false],
      order_correct: [''],
      pair_id: [`left_${nextPairNumber}`],
      is_intrus: [false]
    });

    const rightAnswerForm = this.fb.group({
      answer: ['', Validators.required],
      is_correct: [false],
      order_correct: [''],
      pair_id: [`right_${nextPairNumber}`],
      is_intrus: [false]
    });

    answers.push(leftAnswerForm);
    answers.push(rightAnswerForm);
  }

  getAnswerPlaceholder(questionType: string, answerIndex: number): string {
    switch (questionType) {
      case 'MCQ':
        return `Option ${answerIndex + 1}`;
      case 'multiple_choice':
        return `Choix ${answerIndex + 1}`;
      case 'right_order':
        return `Élément ${answerIndex + 1} à ordonner`;
      case 'find_the_intruder':
        return `Élément ${answerIndex + 1}`;
      case 'blind_test':
        return 'Réponse du blind test';
      case 'matching':
        return 'Élément à associer';
      default:
        return `Réponse ${answerIndex + 1}`;
    }
  }
}
