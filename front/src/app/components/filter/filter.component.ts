import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-filter',
  templateUrl: './filter.component.html',
  imports: [
    FormsModule,
    CommonModule
  ],
  styleUrls: ['./filter.component.scss']
})
export class FilterComponent {
  @Input() filterOptions: { label: string; options: string[] }[] = [];
  @Input() filters: { [key: string]: string } = {};
  @Output() filterChange = new EventEmitter<{ [key: string]: string }>();

  onFilterChange(key: string, value: string): void {
    this.filters[key] = value;
    this.filterChange.emit(this.filters);
  }
}
