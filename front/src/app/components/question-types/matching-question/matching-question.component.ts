import {
  Component,
  Input,
  Output,
  EventEmitter,
  OnInit,
  ElementRef,
  ViewChild,
  AfterViewInit,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { Question, Answer } from '../../../models/quiz.model';

interface Position {
  x: number;
  y: number;
}

interface Connection {
  leftId: string;
  rightId: string;
  leftPos: Position;
  rightPos: Position;
}

@Component({
  selector: 'app-matching-question',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './matching-question.component.html',
  styleUrls: ['./matching-question.component.scss'],
})
export class MatchingQuestionComponent implements OnInit, AfterViewInit {
  @Input() question!: Question;
  @Input() progress: { current: number; total: number; percentage: number } = {
    current: 0,
    total: 0,
    percentage: 0,
  };
  @Output() answerSelected = new EventEmitter<{ [key: string]: string }>();
  @Output() answerValidated = new EventEmitter<void>();

  @ViewChild('canvas', { static: false }) canvasRef!: ElementRef<HTMLCanvasElement>;
  @ViewChild('matchingContainer', { static: false }) containerRef!: ElementRef<HTMLDivElement>;

  leftColumn: Answer[] = [];
  rightColumn: Answer[] = [];
  matches: { [leftId: string]: string } = {};
  connections: Connection[] = [];

  isDrawing = false;
  currentPath: Position[] = [];
  startElement: { id: string; side: 'left' | 'right' } | null = null;

  private ctx!: CanvasRenderingContext2D;

  ngOnInit(): void {
    this.setupMatchingColumns();
  }

  ngAfterViewInit(): void {
    this.setupCanvas();
    this.setupEventListeners();
    this.setupTouchOptimization();
  }

  setupCanvas(): void {
    const canvas = this.canvasRef.nativeElement;
    const container = this.containerRef.nativeElement;

    canvas.width = container.offsetWidth;
    canvas.height = container.offsetHeight;

    this.ctx = canvas.getContext('2d')!;
    this.ctx.lineCap = 'round';
    this.ctx.lineJoin = 'round';

    this.redrawConnections();
  }

  setupEventListeners(): void {
    const canvas = this.canvasRef.nativeElement;

    canvas.addEventListener('mousedown', e => this.startDrawing(e));
    canvas.addEventListener('mousemove', e => this.draw(e));
    canvas.addEventListener('mouseup', e => this.stopDrawing(e));
  }

  private setupTouchOptimization(): void {
    const canvas = this.canvasRef.nativeElement;

    canvas.style.touchAction = 'none';

    let lastTouchTime = 0;

    canvas.addEventListener(
      'touchstart',
      e => {
        e.preventDefault();
        const now = Date.now();
        if (now - lastTouchTime < 50) return;
        lastTouchTime = now;

        const touch = e.touches[0];
        this.startDrawing(touch);
      },
      { passive: false }
    );

    canvas.addEventListener(
      'touchmove',
      e => {
        e.preventDefault();
        if (e.touches.length === 1) {
          this.draw(e.touches[0]);
        }
      },
      { passive: false }
    );

    canvas.addEventListener(
      'touchend',
      e => {
        e.preventDefault();
        if (e.changedTouches.length === 1) {
          this.stopDrawing(e.changedTouches[0]);
        }
      },
      { passive: false }
    );
  }

  setupMatchingColumns(): void {
    const pairs = new Map<string, Answer[]>();

    this.question.answers.forEach(answer => {
      if (answer.pair_id) {
        const pairKey = answer.pair_id.replace(/^(left_|right_)/, '');
        if (!pairs.has(pairKey)) {
          pairs.set(pairKey, []);
        }
        pairs.get(pairKey)!.push(answer);
      }
    });

    pairs.forEach(pairAnswers => {
      const leftItem = pairAnswers.find(a => a.pair_id?.startsWith('left_'));
      const rightItem = pairAnswers.find(a => a.pair_id?.startsWith('right_'));

      if (leftItem) this.leftColumn.push(leftItem);
      if (rightItem) this.rightColumn.push(rightItem);
    });

    this.rightColumn = this.shuffleArray(this.rightColumn);
  }

  getEventPosition(event: MouseEvent | Touch): Position {
    const canvas = this.canvasRef.nativeElement;
    const rect = canvas.getBoundingClientRect();
    return {
      x: event.clientX - rect.left,
      y: event.clientY - rect.top,
    };
  }

  startDrawing(event: MouseEvent | Touch): void {
    const pos = this.getEventPosition(event);
    const element = this.getElementAtPosition(pos);

    if (element) {
      this.isDrawing = true;
      this.startElement = element;
      this.currentPath = [pos];

      this.removeExistingConnection(element.id);
    }
  }

  draw(event: MouseEvent | Touch): void {
    if (!this.isDrawing || !this.startElement) return;

    const pos = this.getEventPosition(event);
    this.currentPath.push(pos);

    this.redrawCanvas();
    this.drawCurrentPath();
  }

  stopDrawing(event: MouseEvent | Touch): void {
    if (!this.isDrawing || !this.startElement) return;

    const pos = this.getEventPosition(event);
    const endElement = this.getElementAtPosition(pos);

    if (endElement && endElement.side !== this.startElement.side) {
      this.createConnection(this.startElement, endElement);
    }

    this.isDrawing = false;
    this.currentPath = [];
    this.startElement = null;
    this.redrawConnections();
  }

  getElementAtPosition(pos: Position): { id: string; side: 'left' | 'right' } | null {
    const elements = document.elementsFromPoint(
      pos.x + this.canvasRef.nativeElement.getBoundingClientRect().left,
      pos.y + this.canvasRef.nativeElement.getBoundingClientRect().top
    );

    for (const element of elements) {
      if (element.classList.contains('matching-item')) {
        const id = element.getAttribute('data-id');
        const side = element.classList.contains('left-item') ? 'left' : 'right';
        if (id) return { id, side };
      }
    }

    return null;
  }

  createConnection(
    start: { id: string; side: 'left' | 'right' },
    end: { id: string; side: 'left' | 'right' }
  ): void {
    const leftId = start.side === 'left' ? start.id : end.id;
    const rightId = start.side === 'right' ? start.id : end.id;

    this.removeExistingConnection(leftId);
    this.removeExistingConnection(rightId);

    const leftPos = this.getElementPosition(leftId, 'left');
    const rightPos = this.getElementPosition(rightId, 'right');

    if (leftPos && rightPos) {
      this.connections.push({
        leftId,
        rightId,
        leftPos,
        rightPos,
      });

      this.matches[leftId] = rightId;
      this.answerSelected.emit(this.formatMatchingAnswer());
    }
  }

  removeExistingConnection(elementId: string): void {
    this.connections = this.connections.filter(
      conn => conn.leftId !== elementId && conn.rightId !== elementId
    );

    Object.keys(this.matches).forEach(leftId => {
      if (leftId === elementId || this.matches[leftId] === elementId) {
        delete this.matches[leftId];
      }
    });
  }

  removeConnection(leftId: string): void {
    this.removeExistingConnection(leftId);
    this.answerSelected.emit(this.formatMatchingAnswer());
    this.redrawConnections();
  }

  getElementPosition(id: string, side: 'left' | 'right'): Position | null {
    const element = document.querySelector(`[data-id="${id}"]`) as HTMLElement;
    if (!element) return null;

    const rect = element.getBoundingClientRect();
    const canvasRect = this.canvasRef.nativeElement.getBoundingClientRect();

    return {
      x: (side === 'left' ? rect.right : rect.left) - canvasRect.left,
      y: rect.top + rect.height / 2 - canvasRect.top,
    };
  }

  redrawCanvas(): void {
    const canvas = this.canvasRef.nativeElement;
    this.ctx.clearRect(0, 0, canvas.width, canvas.height);
  }

  redrawConnections(): void {
    this.redrawCanvas();

    this.connections.forEach(connection => {
      this.drawConnection(connection.leftPos, connection.rightPos, '#257D54', 3);
    });
  }

  drawCurrentPath(): void {
    if (this.currentPath.length < 2) return;

    this.ctx.strokeStyle = '#91DEDA';
    this.ctx.lineWidth = 2;
    this.ctx.globalAlpha = 0.7;

    this.ctx.beginPath();
    this.ctx.moveTo(this.currentPath[0].x, this.currentPath[0].y);

    for (let i = 1; i < this.currentPath.length; i++) {
      this.ctx.lineTo(this.currentPath[i].x, this.currentPath[i].y);
    }

    this.ctx.stroke();
    this.ctx.globalAlpha = 1;
  }

  drawConnection(
    start: Position,
    end: Position,
    color: string = '#257D54',
    width: number = 3
  ): void {
    this.ctx.strokeStyle = color;
    this.ctx.lineWidth = width;

    this.ctx.beginPath();
    this.ctx.moveTo(start.x, start.y);

    const controlPoint1 = { x: start.x + 50, y: start.y };
    const controlPoint2 = { x: end.x - 50, y: end.y };

    this.ctx.bezierCurveTo(
      controlPoint1.x,
      controlPoint1.y,
      controlPoint2.x,
      controlPoint2.y,
      end.x,
      end.y
    );

    this.ctx.stroke();

    this.drawConnectionPoint(start, color);
    this.drawConnectionPoint(end, color);
  }

  drawConnectionPoint(pos: Position, color: string): void {
    this.ctx.fillStyle = color;
    this.ctx.beginPath();
    this.ctx.arc(pos.x, pos.y, 4, 0, 2 * Math.PI);
    this.ctx.fill();
  }

  shuffleArray<T>(array: T[]): T[] {
    const shuffled = [...array];
    for (let i = shuffled.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
  }

  isRightItemConnected(itemId: string): boolean {
    return Object.values(this.matches).includes(itemId.toString());
  }

  getMatchesCount(): number {
    return Object.keys(this.matches).length;
  }

  validateAnswer(): void {
    this.answerSelected.emit(this.matches);
    this.answerValidated.emit();
  }

  private formatMatchingAnswer(): { [key: string]: string } {
    const formattedMatches: { [key: string]: string } = {};

    Object.keys(this.matches).forEach(leftId => {
      const rightId = this.matches[leftId];

      const leftAnswer = this.question.answers.find(a => a.id.toString() === leftId);
      const rightAnswer = this.question.answers.find(a => a.id.toString() === rightId);

      if (leftAnswer && rightAnswer) {
        formattedMatches[leftAnswer.pair_id || leftId] = rightAnswer.pair_id || rightId;
      }
    });

    return formattedMatches;
  }

  canValidate(): boolean {
    return Object.keys(this.matches).length === this.leftColumn.length;
  }

  getItemLetter(index: number): string {
    return String.fromCharCode(65 + index);
  }

  selectedLeftForKeyboard: string | null = null;
  focusedElement: string | null = null;
  isKeyboardMode = false;

  onKeyDown(event: KeyboardEvent, itemId: string, side: 'left' | 'right'): void {
    this.isKeyboardMode = true;

    switch (event.key) {
      case 'Enter':
      case ' ':
        event.preventDefault();
        this.handleKeyboardSelection(itemId, side);
        break;
      case 'Escape':
        this.clearKeyboardSelection();
        break;
      case 'Delete':
      case 'Backspace':
        if (side === 'left' && this.matches[itemId]) {
          event.preventDefault();
          this.removeConnection(itemId);
        }
        break;
    }
  }

  handleKeyboardSelection(itemId: string, side: 'left' | 'right'): void {
    if (side === 'left') {
      if (this.selectedLeftForKeyboard === itemId) {
        this.selectedLeftForKeyboard = null;
      } else {
        this.selectedLeftForKeyboard = itemId;
      }
    } else if (side === 'right' && this.selectedLeftForKeyboard) {
      this.createKeyboardConnection(this.selectedLeftForKeyboard, itemId);
      this.selectedLeftForKeyboard = null;
    }
  }

  createKeyboardConnection(leftId: string, rightId: string): void {
    this.removeExistingConnection(leftId);
    this.removeExistingConnection(rightId);

    const leftPos = this.getElementPosition(leftId, 'left');
    const rightPos = this.getElementPosition(rightId, 'right');

    if (leftPos && rightPos) {
      this.connections.push({
        leftId,
        rightId,
        leftPos,
        rightPos,
      });

      this.matches[leftId] = rightId;
      this.answerSelected.emit(this.formatMatchingAnswer());
      this.redrawConnections();
    }
  }

  clearKeyboardSelection(): void {
    this.selectedLeftForKeyboard = null;
  }

  onFocus(itemId: string): void {
    this.focusedElement = itemId;
  }

  onBlur(): void {
    this.focusedElement = null;
  }

  getAriaLabel(item: Answer, side: 'left' | 'right', index: number): string {
    const position = side === 'left' ? `${index + 1}` : this.getItemLetter(index);
    const baseLabel = `${side === 'left' ? 'Élément' : 'Association'} ${position}: ${item.answer}`;

    if (side === 'left') {
      const connectedRight = this.matches[item.id.toString()];
      if (connectedRight) {
        const rightItem = this.rightColumn.find(r => r.id.toString() === connectedRight);
        return `${baseLabel}. Connecté à ${rightItem?.answer}. Appuyez sur Suppr pour supprimer la connexion.`;
      } else if (this.selectedLeftForKeyboard === item.id.toString()) {
        return `${baseLabel}. Sélectionné. Choisissez une association à droite ou appuyez sur Échap pour annuler.`;
      } else {
        return `${baseLabel}. Appuyez sur Entrée pour sélectionner.`;
      }
    } else {
      const isConnected = this.isRightItemConnected(item.id.toString());
      if (isConnected) {
        return `${baseLabel}. Déjà connecté.`;
      } else if (this.selectedLeftForKeyboard) {
        return `${baseLabel}. Appuyez sur Entrée pour connecter.`;
      } else {
        return `${baseLabel}. Sélectionnez d'abord un élément à gauche.`;
      }
    }
  }

  getAriaDescribedBy(): string {
    return 'matching-instructions';
  }

  getFlowerShape(index: number): string {
    return '/assets/svg/background_flower.svg';
  }

  getConnectionColor(leftId: string): string {
    const colors = [
      'var(--color-primary)',
      'var(--color-secondary)', 
      'var(--color-accent)',
      'var(--color-success)',
      'var(--color-warning)',
      'var(--color-danger)',
      'var(--color-info)',
    ];
    
    const colorIndex = parseInt(leftId) % colors.length;
    return colors[colorIndex];
  }

  getConnectedLeftId(rightId: string): string {
    // Trouve l'ID de gauche qui est connecté à cet élément de droite
    for (const [leftId, connectedRightId] of Object.entries(this.matches)) {
      if (connectedRightId === rightId) {
        return leftId;
      }
    }
    return '';
  }

  getHueRotation(id: string): number {
    return parseInt(id) * 60;
  }
}
