import { Component, OnInit, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  FormBuilder,
  FormGroup,
  FormArray,
  Validators,
  ReactiveFormsModule,
  FormsModule,
  AbstractControl,
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

interface Difficulty {
  value: string;
  label: string;
}

@Component({
  selector: 'app-quiz-creation',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule],
  templateUrl: './quiz-creation.component.html',
  styleUrls: ['./quiz-creation.component.scss'],
})
export class QuizCreationComponent implements OnInit {
  quizForm!: FormGroup;
  typeQuestions: TypeQuestion[] = [];
  categories: Category[] = [];
  filteredCategories: Category[] = [];
  groups: Group[] = [];
  statuses: Status[] = [];
  difficulties: Difficulty[] = [
    { value: 'easy', label: 'Facile' },
    { value: 'medium', label: 'Moyen' },
    { value: 'hard', label: 'Difficile' },
  ];
  isSubmitting = false;
  isEditMode = false;
  quizId: number | null = null;

  categorySearch = '';
  showCategoryDropdown = false;
  selectedCategory: Category | null = null;

  highlightColor: string = '';

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
    this.generateRandomColor();
    this.quizForm = this.createQuizForm();

    // Charger d'abord les types de questions
    this.loadTypeQuestions().then(() => {
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
    });
  }

  private generateRandomColor(): void {
    const colors = ['#257D54', '#FAA24B', '#D30D4C'];

    const index = Math.floor(Math.random() * colors.length);
    this.highlightColor = colors[index];
  }

  private createQuizForm(): FormGroup {
    const form = this.fb.group({
      title: ['', [Validators.required, Validators.minLength(3)]],
      description: ['', [Validators.required, Validators.minLength(10)]],
      status: ['draft', Validators.required],
      is_public: [true, Validators.required],
      category: [null, Validators.required],
      groups: [[]],
      questions: this.fb.array([]),
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
      difficulty: ['easy', Validators.required],
      answers: this.fb.array([]),
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
    const questionType = this.questions.at(questionIndex).get('type_question')?.value;
    const answers = this.getAnswers(questionIndex);
    const maxAnswers = this.getMaxAnswersForType(questionType);

    if (answers.length >= maxAnswers) {
      return;
    }

    const answerForm = this.fb.group({
      answer: ['', Validators.required],
      is_correct: [false],
      order_correct: [''],
      pair_id: [''],
      is_intrus: [false],
    });

    answers.push(answerForm);
  }

  removeAnswer(questionIndex: number, answerIndex: number): void {
    const questionType = this.questions.at(questionIndex).get('type_question')?.value;
    const answers = this.getAnswers(questionIndex);
    const minAnswers = this.getMinAnswersForType(questionType);

    if (answers.length > minAnswers) {
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
    } else if (questionType === 'true_false') {
      this.initializeTrueFalseAnswers(questionIndex);
    } else {
      for (let i = 0; i < minAnswers; i++) {
        this.addAnswer(questionIndex);
      }
    }
  }

  private initializeMatchingPairs(questionIndex: number): void {
    const answers = this.getAnswers(questionIndex);
    const alreadyPaired = answers.controls.some(
      (control: AbstractControl) => !!control.get('pair_id')?.value
    );
    if (alreadyPaired) return;

    for (let i = 0; i < answers.length; i++) {
      const pairNumber = Math.floor(i / 2) + 1;
      const side = i % 2 === 0 ? 'left' : 'right';
      const pairId = `${side}_${pairNumber}`;
      answers.at(i).get('pair_id')?.setValue(pairId);
    }
  }

  private initializeTrueFalseAnswers(questionIndex: number): void {
    const answers = this.getAnswers(questionIndex);

    // Ajouter la réponse "Vrai"
    const trueAnswer = this.fb.group({
      answer: ['Vrai', Validators.required],
      is_correct: [true],
      order_correct: [null],
      pair_id: [null],
      is_intrus: [false],
    });
    answers.push(trueAnswer);

    const falseAnswer = this.fb.group({
      answer: ['Faux', Validators.required],
      is_correct: [false],
      order_correct: [null],
      pair_id: [null],
      is_intrus: [false],
    });
    answers.push(falseAnswer);
  }

  onTrueFalseCorrectAnswerChange(questionIndex: number, isTrue: boolean): void {
    const answers = this.getAnswers(questionIndex);

    // Mettre à jour les réponses Vrai/Faux
    if (answers.length >= 2) {
      // La première réponse est "Vrai", la seconde est "Faux"
      answers.at(0).get('is_correct')?.setValue(isTrue);
      answers.at(1).get('is_correct')?.setValue(!isTrue);
    }
  }

  getTrueFalseCorrectAnswer(questionIndex: number): boolean {
    const answers = this.getAnswers(questionIndex);
    if (answers.length >= 2) {
      return answers.at(0).get('is_correct')?.value || false;
    }
    return true; // Par défaut, "Vrai" est la bonne réponse
  }

  private getMinAnswersForType(questionType: string): number {
    const MIN_ANSWERS_CONFIG = {
      MCQ: 3,
      multiple_choice: 3,
      right_order: 3,
      matching: 4,
      find_the_intruder: 3,
      blind_test: 1,
      true_false: 2,
    } as const;

    return MIN_ANSWERS_CONFIG[questionType as keyof typeof MIN_ANSWERS_CONFIG] || 2;
  }

  private getMaxAnswersForType(questionType: string): number {
    const MAX_ANSWERS_CONFIG = {
      MCQ: 6,
      multiple_choice: 6,
      right_order: 8,
      matching: 10,
      find_the_intruder: 3,
      blind_test: 1,
    } as const;

    return MAX_ANSWERS_CONFIG[questionType as keyof typeof MAX_ANSWERS_CONFIG] || 8;
  }

  getQuestionTypeLabel(type: string): string {
    const TYPE_LABELS = {
      MCQ: 'QCM',
      multiple_choice: 'Choix multiple',
      right_order: 'Remise dans le bon ordre',
      matching: "Association d'éléments",
      find_the_intruder: 'Intrus',
      blind_test: 'Blind Test',
    } as const;

    return TYPE_LABELS[type as keyof typeof TYPE_LABELS] || type;
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
    return this.getAnswers(questionIndex)
      .controls.find(ctrl => ctrl.get('pair_id')?.value === pairId)
      ?.get('answer');
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

  loadTypeQuestions(): Promise<void> {
    return new Promise((resolve, reject) => {
      this.quizService.getTypeQuestions().subscribe({
        next: res => {
          this.typeQuestions = res;
          resolve();
        },
        error: error => {
          console.error('Erreur lors du chargement des types de questions:', error);
          reject(error);
        },
      });
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
    this.quizService.getQuizForEdit(id).subscribe(res => {
      this.quizForm.patchValue({
        title: res.title,
        description: res.description,
        status: res.status,
        is_public: res.isPublic,
        category: res.category?.id || null,
        groups: res.groups.map((g: any) => g.id),
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
        console.log('DEBUG: Question reçue:', q);
        console.log('DEBUG: Type de question:', q.type_question);
        console.log('DEBUG: TypeQuestions disponibles:', this.typeQuestions);

        let questionTypeKey = '';
        if (typeof q.type_question === 'object' && q.type_question !== null) {
          // Utiliser le name du backend (ex: "QCM") au lieu de la key
          questionTypeKey = q.type_question.name || q.type_question.key || '';
          console.log('DEBUG: Type object, clé trouvée:', questionTypeKey);
        } else if (typeof q.type_question === 'string') {
          questionTypeKey = q.type_question;
          console.log('DEBUG: Type string, clé:', questionTypeKey);
        } else {
          console.log('DEBUG: Type inconnu:', typeof q.type_question, q.type_question);
        }

        const questionForm = this.fb.group({
          question: [q.question || '', [Validators.required, Validators.minLength(5)]],
          type_question: [questionTypeKey, Validators.required],
          difficulty: [q.difficulty || 'easy', Validators.required],
          answers: this.fb.array([]),
        });

        questionsArray.push(questionForm);
        const questionIndex = questionsArray.length - 1;

        if (q.answers && q.answers.length) {
          q.answers.forEach((ans: any) => {
            const answerForm = this.fb.group({
              answer: [ans.answer || '', Validators.required],
              is_correct: [ans.is_correct || false],
              order_correct: [ans.order_correct ?? ''],
              pair_id: [ans.pair_id || ''],
              is_intrus: [ans.is_intrus || false],
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
    if (this.quizForm.invalid) {
      return;
    }

    if (this.typeQuestions.length === 0) {
      alert('Erreur: Types de questions non chargés. Veuillez rafraîchir la page.');
      return;
    }

    this.isSubmitting = true;
    const formValue = this.quizForm.value;

    // Construire le payload sans utiliser ...formValue pour éviter l'écrasement
    const mappedQuestions = formValue.questions.map((q: any, index: number) => {
      const typeQuestionId = this.getTypeQuestionId(q.type_question);

      if (typeQuestionId === 0) {
        throw new Error(`Type de question invalide: ${q.type_question}`);
      }

      const questionMapped = {
        question: q.question,
        type_question: typeQuestionId, // Utilise l'ID numérique
        difficulty: q.difficulty,
        answers: q.answers.map((a: any) => ({
          ...a,
          order_correct: a.order_correct || null,
        })),
      };

      return questionMapped;
    });

    const payload = {
      title: formValue.title,
      description: formValue.description,
      status: formValue.status,
      groups: formValue.groups,
      category_id: formValue.category,
      isPublic: formValue.is_public,
      questions: mappedQuestions,
    };

    // Plus besoin de delete car on construit le payload proprement

    // Vérifier qu'aucune question n'a un type_question à 0
    const hasInvalidQuestions = payload.questions.some((q: any) => q.type_question === 0);
    if (hasInvalidQuestions) {
      this.isSubmitting = false;
      alert(
        'Erreur: Une ou plusieurs questions ont un type invalide. Veuillez vérifier et réessayer.'
      );
      return;
    }

    const action = this.isEditMode
      ? this.quizService.updateQuiz(this.quizId!, payload)
      : this.quizService.createQuiz(payload);

    action.subscribe({
      next: response => {
        this.isSubmitting = false;
        this.router.navigate(['/quiz']);
      },
      error: error => {
        console.error('Erreur lors de la création/modification du quiz:', {
          status: error.status,
          message: error.message,
          error: error.error,
        });
        this.isSubmitting = false;
      },
    });
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
      is_intrus: [false],
    });

    const rightAnswerForm = this.fb.group({
      answer: ['', Validators.required],
      is_correct: [false],
      order_correct: [''],
      pair_id: [`right_${nextPairNumber}`],
      is_intrus: [false],
    });

    answers.push(leftAnswerForm);
    answers.push(rightAnswerForm);
  }

  getAnswerPlaceholder(questionType: string, answerIndex: number): string {
    const PLACEHOLDER_GENERATORS = {
      MCQ: (index: number) => `Option ${index + 1}`,
      multiple_choice: (index: number) => `Choix ${index + 1}`,
      right_order: (index: number) => `Élément ${index + 1} à ordonner`,
      find_the_intruder: (index: number) => `Élément ${index + 1}`,
      blind_test: () => 'Réponse du blind test',
      matching: () => 'Élément à associer',
    } as const;

    const generator = PLACEHOLDER_GENERATORS[questionType as keyof typeof PLACEHOLDER_GENERATORS];
    return generator ? generator(answerIndex) : `Réponse ${answerIndex + 1}`;
  }

  canAddAnswer(questionIndex: number): boolean {
    const questionType = this.questions.at(questionIndex).get('type_question')?.value;
    const answers = this.getAnswers(questionIndex);
    const maxAnswers = this.getMaxAnswersForType(questionType);
    return answers.length < maxAnswers;
  }

  canRemoveAnswer(questionIndex: number): boolean {
    const questionType = this.questions.at(questionIndex).get('type_question')?.value;
    const answers = this.getAnswers(questionIndex);
    const minAnswers = this.getMinAnswersForType(questionType);
    return answers.length > minAnswers;
  }

  /**
   * Convertit la clé du type de question en ID numérique
   */
  private getTypeQuestionId(typeKey: string): number {
    // Chercher d'abord par name (format backend), puis par key (fallback)
    const typeQuestion =
      this.typeQuestions.find(t => t.name === typeKey) ||
      this.typeQuestions.find(t => t.key === typeKey);

    // Convertir l'ID en nombre si c'est une string
    const rawId = typeQuestion ? typeQuestion.id : 0;
    const result = typeof rawId === 'string' ? parseInt(rawId, 10) : rawId;

    if (result === 0 || isNaN(result)) {
      console.error('ATTENTION: Type de question non trouvé ou ID invalide!', {
        typeKey,
        rawId,
        result,
        availableTypes: this.typeQuestions.map(t => ({ id: t.id, key: t.key, name: t.name })),
      });
    }

    return result;
  }
}
