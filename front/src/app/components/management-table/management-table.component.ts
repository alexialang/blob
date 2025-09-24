import { Component, Input, Output, EventEmitter, TemplateRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { PaginationComponent } from '../pagination/pagination.component';

export interface TableColumn {
  key: string;
  label: string;
  sortable?: boolean;
  width?: string;
  template?: TemplateRef<any>;
}

export interface FilterOption {
  key: string;
  label: string;
  options: Array<{ value: string; label: string }>;
}

export interface ManagementTableConfig {
  title: string;
  highlightText?: string;
  highlightColor?: string;
  columns: TableColumn[];
  filters: FilterOption[];
  dropdownActions?: string[];
  showKeywordFilter?: boolean;
  showSelectAll?: boolean;
}

@Component({
  selector: 'app-management-table',
  standalone: true,
  imports: [CommonModule, FormsModule, PaginationComponent],
  templateUrl: './management-table.component.html',
  styleUrls: ['./management-table.component.scss'],
})
export class ManagementTableComponent {
  @Input() config!: ManagementTableConfig;
  @Input() data: any[] = [];
  @Input() loading = false;
  @Input() error = false;
  @Input() pageSize = 20;
  @Output() filterChange = new EventEmitter<{ [key: string]: any }>();
  @Output() sortChange = new EventEmitter<{ column: string; direction: 'asc' | 'desc' }>();
  @Output() actionClick = new EventEmitter<{ action: string; item?: any }>();
  @Output() pageChange = new EventEmitter<number>();
  @Output() selectionChange = new EventEmitter<any[]>();

  filters: { [key: string]: any } = {};
  keywordFilter = '';
  sortColumn = '';
  sortDirection: 'asc' | 'desc' = 'asc';
  allSelected = false;
  page = 1;
  size = 'm' as const;
  dropdownOpen = false;

  get pagedData() {
    const start = (this.page - 1) * this.pageSize;
    return this.data.slice(start, start + this.pageSize);
  }

  get selectedItems() {
    return this.data.filter(item => item.selected);
  }

  get hasSelection() {
    return this.selectedItems.length > 0;
  }

  applyFilters() {
    const allFilters = {
      ...this.filters,
      keyword: this.keywordFilter,
    };
    this.filterChange.emit(allFilters);
  }

  sortBy(column: string) {
    const newDirection =
      this.sortColumn === column && this.sortDirection === 'asc' ? 'desc' : 'asc';
    this.sortColumn = column;
    this.sortDirection = newDirection;
    this.sortChange.emit({ column, direction: newDirection });
  }

  toggleAll(selected: boolean) {
    this.allSelected = selected;
    this.data.forEach(item => (item.selected = selected));
    this.selectionChange.emit(this.selectedItems);
  }

  onItemSelectionChange() {
    this.allSelected = this.data.length > 0 && this.data.every(item => item.selected);
    this.selectionChange.emit(this.selectedItems);
  }

  onPageChanged(newPage: number) {
    this.page = newPage;
    this.pageChange.emit(newPage);
  }

  onActionClick(action: string, item?: any) {
    this.actionClick.emit({ action, item });
  }

  onDropdownAction(action: string) {
    this.dropdownOpen = false;
    this.actionClick.emit({ action });
  }
}
