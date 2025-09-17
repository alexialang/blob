import { Directive, Input, TemplateRef, ViewContainerRef, OnInit, OnDestroy } from '@angular/core';
import { AuthService } from '../services/auth.service';
import { Subject, takeUntil } from 'rxjs';

@Directive({
  selector: '[hasAnyPermission]',
  standalone: true,
})
export class HasAnyPermissionDirective implements OnInit, OnDestroy {
  @Input() hasAnyPermission!: string[];
  @Input() hasAnyPermissionElse?: TemplateRef<any>;

  private destroy$ = new Subject<void>();
  private hasView = false;

  constructor(
    private templateRef: TemplateRef<any>,
    private viewContainer: ViewContainerRef,
    private authService: AuthService
  ) {}

  ngOnInit(): void {
    this.authService
      .hasAnyPermission(this.hasAnyPermission)
      .pipe(takeUntil(this.destroy$))
      .subscribe(hasAnyPermission => {
        if (hasAnyPermission && !this.hasView) {
          this.viewContainer.clear();
          this.viewContainer.createEmbeddedView(this.templateRef);
          this.hasView = true;
        } else if (!hasAnyPermission && this.hasView) {
          this.viewContainer.clear();
          this.hasView = false;

          if (this.hasAnyPermissionElse) {
            this.viewContainer.createEmbeddedView(this.hasAnyPermissionElse);
          }
        }
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }
}
