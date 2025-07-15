import { Component, OnInit, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
    FormBuilder,
    FormGroup,
    FormArray,
    Validators,
    ReactiveFormsModule,
    FormsModule
} from '@angular/forms';
import { Router } from '@angular/router';
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
    imports: [
        CommonModule,
        ReactiveFormsModule,
        FormsModule
    ],
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

    categorySearch = '';
    showCategoryDropdown = false;
    selectedCategory: Category | null = null;

    constructor(
        private readonly fb: FormBuilder,
        private readonly router: Router,
        private readonly quizService: QuizManagementService
    ) { }

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
        const answers = this.getAnswers(questionIndex);

        while (answers.length > 0) {
            answers.removeAt(0);
        }

        this.initializeAnswersForType(questionIndex, questionType);
    }

    private initializeAnswersForType(questionIndex: number, questionType: string): void {
        const minAnswers = this.getMinAnswersForType(questionType);

        for (let i = 0; i < minAnswers; i++) {
            this.addAnswer(questionIndex);
        }

        if (questionType === 'matching') {
            this.initializeMatchingPairs(questionIndex);
        }
    }

    private initializeMatchingPairs(questionIndex: number): void {
        const answers = this.getAnswers(questionIndex);
        const totalAnswers = answers.length;

        for (let i = 0; i < totalAnswers; i++) {
            const pairNumber = Math.floor(i / 2) + 1;
            const side = i % 2 === 0 ? 'left' : 'right';
            const pairId = `${side}_${pairNumber}`;

            answers.at(i).get('pair_id')?.setValue(pairId);
        }
    }

    private getMinAnswersForType(questionType: string): number {
        switch (questionType) {
            case 'MCQ':
            case 'multiple_choice':
                return 2;
            case 'right_order':
                return 3;
            case 'matching':
                return 4;
            case 'find_the_intruder':
                return 4;
            case 'blind_test':
                return 1;
            default:
                return 2;
        }
    }

    getQuestionTypeLabel(questionType: string): string {
        const typeQuestion = this.typeQuestions.find(t => t.key === questionType);
        return typeQuestion?.name || 'Type inconnu';
    }

    isQuestionTypeRequiringOrder(questionType: string): boolean {
        return questionType === 'right_order';
    }

    isQuestionTypeRequiringMatching(questionType: string): boolean {
        return questionType === 'matching';
    }

    isQuestionTypeRequiringIntrus(questionType: string): boolean {
        return questionType === 'find_the_intruder';
    }

    getAnswerPlaceholder(questionType: string, answerIndex: number): string {
        switch (questionType) {
            case 'MCQ':
            case 'multiple_choice':
                return `Réponse ${answerIndex + 1}`;
            case 'right_order':
                return `Élément ${answerIndex + 1} à ordonner`;
            case 'matching':
                return answerIndex % 2 === 0 ? `Élément A${Math.floor(answerIndex / 2) + 1}` : `Élément B${Math.floor(answerIndex / 2) + 1}`;
            case 'find_the_intruder':
                return `Option ${answerIndex + 1}`;
            case 'blind_test':
                return 'Réponse attendue';
            default:
                return 'Réponse';
        }
    }

    getStatusName(status: string): string {
        const statusObj = this.statuses.find(s => s.value === status);
        return statusObj?.name ?? 'Statut inconnu';
    }

    getMinAnswersForTypePublic(questionType: string): number {
        return this.getMinAnswersForType(questionType);
    }

    getMatchingPairs(questionIndex: number): number[] {
        const answers = this.getAnswers(questionIndex);
        const pairCount = Math.ceil(answers.length / 2);
        return Array.from({ length: pairCount }, (_, i) => i);
    }

    getAnswerControlByPairId(questionIndex: number, pairId: string): any {
        const answers = this.getAnswers(questionIndex);
        const answerIndex = answers.controls.findIndex(control =>
            control.get('pair_id')?.value === pairId
        );

        if (answerIndex >= 0) {
            return answers.at(answerIndex).get('answer');
        }

        const newAnswer = this.fb.group({
            answer: ['', Validators.required],
            is_correct: [false],
            order_correct: [''],
            pair_id: [pairId],
            is_intrus: [false]
        });

        answers.push(newAnswer);
        return newAnswer.get('answer');
    }

    addMatchingPair(questionIndex: number): void {
        const answers = this.getAnswers(questionIndex);
        const pairNumber = Math.ceil(answers.length / 2) + 1;

        const leftAnswer = this.fb.group({
            answer: ['', Validators.required],
            is_correct: [false],
            order_correct: [''],
            pair_id: [`left_${pairNumber}`],
            is_intrus: [false]
        });

        const rightAnswer = this.fb.group({
            answer: ['', Validators.required],
            is_correct: [false],
            order_correct: [''],
            pair_id: [`right_${pairNumber}`],
            is_intrus: [false]
        });

        answers.push(leftAnswer);
        answers.push(rightAnswer);
    }

    filterCategories(): void {
        const search = this.categorySearch.trim().toLowerCase();
        this.filteredCategories = this.categories.filter(cat =>
            cat.name.toLowerCase().includes(search)
        );
    }

    selectCategory(cat: Category): void {
        this.selectedCategory = cat;
        this.categorySearch = cat.name;
        this.quizForm.patchValue({ category: cat.id });
        this.showCategoryDropdown = false;
    }

    clearCategory(): void {
        this.selectedCategory = null;
        this.categorySearch = '';
        this.quizForm.patchValue({ category: null });
        this.filteredCategories = [...this.categories];
    }

    private loadCategories(): void {
        this.quizService.getCategories().subscribe({
            next: (categories) => {
                this.categories = categories;
                this.filteredCategories = [...categories];
            },
            error: (error) => {
                console.error('Erreur lors du chargement des catégories', error);
            }
        });
    }

    private loadTypeQuestions(): void {
        this.quizService.getTypeQuestions().subscribe({
            next: (types) => {
                console.log('Types de questions reçus:', types);
                this.typeQuestions = types;
            },
            error: (error) => {
                console.error('Erreur lors du chargement des questions', error);
            }
        });
    }

    private loadGroups(): void {
        this.quizService.getGroups().subscribe({
            next: (groups) => {
                this.groups = groups;
            },
            error: (error) => {
                console.error('Erreur lors du chargement des groupes', error);
            }
        });
    }

    private loadStatuses(): void {
        this.quizService.getStatuses().subscribe({
            next: (statuses) => {
                console.log('Statuts reçus:', statuses);
                this.statuses = statuses;
            },
            error: (error) => {
                console.error('Erreur lors du chargement des statuts', error);
                this.statuses = [
                    { id: 1, name: 'Brouillon', value: 'draft' },
                    { id: 2, name: 'En ligne', value: 'published' },
                    { id: 3, name: 'Archivé', value: 'archived' }
                ];
            }
        });
    }

    onSubmit(): void {
        if (this.quizForm.valid) {
            this.isSubmitting = true;
            const formData = { ...this.quizForm.value };

            this.quizService.createQuiz(formData).subscribe({
                next: (response) => {
                    console.log('Quiz créé avec succès!', response);
                    this.router.navigate(['/quiz']);
                },
                error: (error) => {
                    console.error('Erreur lors de la création du quiz', error);
                    this.isSubmitting = false;
                }
            });
        } else {
            this.markFormGroupTouched(this.quizForm);
        }
    }

    private markFormGroupTouched(formGroup: FormGroup): void {
        Object.keys(formGroup.controls).forEach(key => {
            const control = formGroup.get(key);
            control?.markAsTouched();

            if (control instanceof FormGroup) {
                this.markFormGroupTouched(control);
            } else if (control instanceof FormArray) {
                control.controls.forEach(arrayControl => {
                    if (arrayControl instanceof FormGroup) {
                        this.markFormGroupTouched(arrayControl);
                    }
                });
            }
        });
    }

    cancel(): void {
        this.router.navigate(['/quiz']);
    }
}
