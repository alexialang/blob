import {
  Component,
  Input,
  Output,
  EventEmitter,
  ChangeDetectionStrategy,
} from '@angular/core';
import { TuiPagination} from '@taiga-ui/kit';

@Component({
  selector: 'app-pagination',
  standalone: true,
  imports: [TuiPagination],
  templateUrl: './pagination.component.html',
  styleUrls: ['./pagination.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PaginationComponent {
  @Input() totalItems = 0;

  @Input() pageSize = 10;

  @Input() page = 1;

  @Input() activePadding = 2;

  @Output() pageChange = new EventEmitter<number>();

  get totalPages(): number {
    return Math.ceil(this.totalItems / this.pageSize) || 1;
  }

  get tuiIndex(): number {
    return Math.min(Math.max(this.page - 1, 0), this.totalPages - 1);
  }

  onTuiIndexChange(newIndex: number): void {
    const newPage = newIndex + 1;
    if (newPage !== this.page) {
      this.page = newPage;
      this.pageChange.emit(newPage);
    }
  }
}
