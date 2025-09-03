import { Component, EventEmitter, Output, ElementRef, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TuiDialogService, TuiAlertService } from '@taiga-ui/core';
import { CompanyService } from '../../services/company.service';


@Component({
  selector: 'app-add-company-modal',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule
  ],
  templateUrl: './add-company-modal.component.html',
  styleUrls: ['./add-company-modal.component.scss']
})
export class AddCompanyModalComponent {
  @Output() companyCreated = new EventEmitter<any>();
  @Output() cancelled = new EventEmitter<void>();
  
  companyName: string = '';

  constructor(
    private companyService: CompanyService,
    private dialogService: TuiDialogService,
    private alerts: TuiAlertService,
    private elementRef: ElementRef
  ) {}

  @HostListener('modalEscape')
  onModalEscape() {
    this.cancel();
  }

  save(): void {
    if (!this.companyName?.trim()) return;

    this.companyService.createCompany({ name: this.companyName.trim() })
      .subscribe({
        next: (company) => {
          this.alerts.open('Entreprise créée avec succès !', { appearance: 'success' }).subscribe();
          this.companyCreated.emit(company);
        },
        error: (error) => {
          this.alerts.open('Erreur lors de la création de l\'entreprise', { appearance: 'error' }).subscribe();
          console.error('Erreur création entreprise:', error);
        }
      });
  }

  cancel(): void {
    this.cancelled.emit();
  }
}
