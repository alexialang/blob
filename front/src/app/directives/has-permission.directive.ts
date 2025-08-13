import { Directive, Input, TemplateRef, ViewContainerRef, effect, DestroyRef, inject } from '@angular/core';
import { AuthService } from '../services/auth.service';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

@Directive({
  selector: '[appHasAccess]',
  standalone: true
})
export class HasAccessDirective {
  private authService = inject(AuthService);
  private templateRef = inject(TemplateRef<any>);
  private viewContainer = inject(ViewContainerRef);
  private destroyRef = inject(DestroyRef);

  @Input() set appHasAccess(config: { permission?: string; role?: string }) {
    if (config.permission) {
      this.checkPermission(config.permission);
    } else if (config.role) {
      this.checkRole(config.role);
    }
  }

  private checkPermission(permission: string) {
    this.authService.hasPermission(permission)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(hasAccess => this.updateView(hasAccess));
  }

  private checkRole(role: string) {
    this.authService.hasRole(role)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(hasAccess => this.updateView(hasAccess));
  }

  private updateView(hasAccess: boolean) {
    this.viewContainer.clear();
    if (hasAccess) {
      this.viewContainer.createEmbeddedView(this.templateRef);
    }
  }
}
