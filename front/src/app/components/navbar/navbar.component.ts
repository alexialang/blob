import {
  ChangeDetectionStrategy,
  Component,
  signal
} from '@angular/core';
import {
  TuiButton,
  TuiLink,
  TuiPopup,
} from '@taiga-ui/core';
import {
  TuiAvatar,
  TuiDrawer, TuiProgressBar,
} from '@taiga-ui/kit';
import {TuiIcon} from '@taiga-ui/core/components/icon';


@Component({
  selector: 'navbar',
  standalone: true,
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    TuiButton,
    TuiLink,
    TuiDrawer,
    TuiPopup,
    TuiAvatar,
    TuiProgressBar,
    TuiIcon,

  ],
})
export class NavbarComponent {
  protected readonly open = signal(false);

  toggle(): void {
    this.open.set(!this.open());
  }

  close(): void {
    this.open.set(false);
  }

}

