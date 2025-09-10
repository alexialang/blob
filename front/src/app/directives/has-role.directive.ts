import { Directive, Input, TemplateRef, ViewContainerRef, OnInit, OnDestroy } from '@angular/core';
import { AuthService } from '../services/auth.service';
import { Subject, takeUntil } from 'rxjs';

@Directive({
  selector: '[hasRole]',
  standalone: true,
})
export class HasRoleDirective implements OnInit, OnDestroy {
  @Input() hasRole!: string;
  @Input() hasRoleElse?: TemplateRef<any>;

  private destroy$ = new Subject<void>();
  private hasView = false;

  constructor(
    private templateRef: TemplateRef<any>,
    private viewContainer: ViewContainerRef,
    private authService: AuthService
  ) {}

  ngOnInit(): void {
    this.authService
      .hasRole(this.hasRole)
      .pipe(takeUntil(this.destroy$))
      .subscribe(hasRole => {
        if (hasRole && !this.hasView) {
          this.viewContainer.clear();
          this.viewContainer.createEmbeddedView(this.templateRef);
          this.hasView = true;
        } else if (!hasRole && this.hasView) {
          this.viewContainer.clear();
          this.hasView = false;

          if (this.hasRoleElse) {
            this.viewContainer.createEmbeddedView(this.hasRoleElse);
          }
        }
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }
}
