import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../services/auth.service';
import { Observable, of, combineLatest } from 'rxjs';
import { map, catchError, switchMap } from 'rxjs/operators';
import { DomSanitizer, SafeUrl, SafeHtml } from '@angular/platform-browser';
import { HasPermissionDirective } from '../../directives/has-permission.directive';
import { HttpClient } from '@angular/common/http';

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [RouterLink, CommonModule, HasPermissionDirective],
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent implements OnInit {
  userName$: Observable<string> = of('');
  userAvatar$: Observable<SafeUrl> = of('./assets/svg/logo.svg');
  userAvatarSvg$: Observable<SafeHtml> = of('');
  userAvatarShape$: Observable<string> = of('circle');
  userAvatarColor$: Observable<string> = of('#257D54');
  isGuest$: Observable<boolean> = of(true);
  showGestionDropdown: boolean = false;
  showProfileDropdown: boolean = false;
  isMobileMenuOpen: boolean = false;

  isAdmin$: Observable<boolean> = of(false);
  userCompanyId$: Observable<number | null> = of(null);

  randomColor: string = '#0B0C1E';

  constructor(
    private authService: AuthService,
    private sanitizer: DomSanitizer,
    private http: HttpClient
  ) {
  }

  ngOnInit() {
    this.generateRandomColor();
    this.loadUserData();

    this.authService.loginStatus$.subscribe(() => {
      this.loadUserData();
    });
  }

  generateRandomColor() {
    const colors = ['var(--color-primary)', 'var(--color-secondary)', 'var(--color-accent)', 'var(--color-pink)'];
    this.randomColor = colors[Math.floor(Math.random() * colors.length)];
  }

  onImageError(event: any) {
    console.error('Image failed to load:', event.target.src);
  }

  onImageLoad(event: any) {
    console.log('Image loaded successfully:', event.target.src);
  }

  // Fonction pour convertir un avatar corps entier en tête d'avatar
  private getHeadAvatarFromFullAvatar(fullAvatarPath: string): string {
    if (!fullAvatarPath) {
      return 'assets/avatars/head_guest.svg';
    }

    // Extraire le nom de fichier de l'avatar
    const fileName = fullAvatarPath.split('/').pop() || '';
    
    // Mapping des avatars corps entier vers les têtes
    const avatarMapping: { [key: string]: string } = {
      'blob_circle.svg': 'circle_head.svg',
      'blob_flower.svg': 'flower_head.svg',
      'blob_flower_blue.svg': 'flower_head.svg', // Même tête pour les deux variantes de fleur
      'blob_pic.svg': 'pic_head.svg',
      'blob_pic_orange.svg': 'pic_head.svg', // Même tête pour les deux variantes de pic
      'blob_wave.svg': 'wave_head.svg'
    };

    // Support pour les noms sans extension (utilisés dans avatar-selection)
    const shapeMapping: { [key: string]: string } = {
      'blob_flower': 'flower_head.svg',
      'blob_circle': 'circle_head.svg', 
      'blob_pic': 'pic_head.svg',
      'blob_wave': 'wave_head.svg'
    };

    // Vérifier d'abord le mapping avec extension
    let headAvatar = avatarMapping[fileName];
    
    // Si pas trouvé, essayer sans extension ou avec le nom de forme directement
    if (!headAvatar) {
      const shapeName = fileName.replace('.svg', '');
      headAvatar = shapeMapping[shapeName];
    }

    if (headAvatar) {
      return `assets/avatars/${headAvatar}`;
    }

    // Fallback vers head_guest.svg si aucune correspondance
    return 'assets/avatars/head_guest.svg';
  }

  // Nouvelle fonction pour convertir directement une forme en tête d'avatar
  private getHeadAvatarFromShape(shape: string): string {
    if (!shape) {
      return 'assets/avatars/head_guest.svg';
    }

    // Mapping direct des formes vers les têtes
    const shapeToHeadMapping: { [key: string]: string } = {
      'blob_flower': 'flower_head.svg',
      'blob_circle': 'circle_head.svg', 
      'blob_pic': 'pic_head.svg',
      'blob_wave': 'wave_head.svg'
    };

    const headAvatar = shapeToHeadMapping[shape];
    if (headAvatar) {
      return `assets/avatars/${headAvatar}`;
    }

    // Fallback vers head_guest.svg si forme inconnue
    return 'assets/avatars/head_guest.svg';
  }

  // Fonction pour charger et personnaliser le SVG avec la couleur
  private loadAvatarSvg(avatarPath: string, color: string): Observable<SafeHtml> {
    return this.http.get(avatarPath, { responseType: 'text' }).pipe(
      map(svgContent => {
        // Remplacer les couleurs par défaut par la couleur utilisateur
        // #257D54 (vert par défaut dans la plupart des têtes d'avatar)
        let coloredSvg = svgContent.replace(/#257D54/g, color);
        
        // Pour head_guest.svg qui utilise #0B0C1E comme couleur principale
        if (avatarPath.includes('head_guest.svg')) {
          coloredSvg = coloredSvg.replace(/#0B0C1E/g, color);
        }
        
        // Forcer les dimensions du SVG pour qu'il prenne toute la taille du conteneur
        coloredSvg = coloredSvg.replace(
          /<svg([^>]*)>/,
          '<svg$1 style="width: 100px !important; height: 100px !important;">'
        );
        
        return this.sanitizer.bypassSecurityTrustHtml(coloredSvg);
      }),
      catchError(error => {
        console.error('Error loading SVG:', error);
        return of(this.sanitizer.bypassSecurityTrustHtml(''));
      })
    );
  }

  loadUserData() {
    this.isGuest$ = of(this.authService.isGuest());

    if (this.authService.isLoggedIn()) {
      this.userName$ = this.authService.getCurrentUser().pipe(
        map(user => {
          return user.pseudo || user.firstName || user.email || 'Utilisateur';
        }),
        catchError(() => of('Utilisateur'))
      );

      this.userAvatar$ = this.authService.getCurrentUser().pipe(
        map(user => {
          console.log('User data:', user);
          if (user.avatar) {
            // Convertir l'avatar corps entier en tête d'avatar
            const headAvatarPath = this.getHeadAvatarFromFullAvatar(user.avatar);
            console.log('Full avatar:', user.avatar, '-> Head avatar:', headAvatarPath);
            return this.sanitizer.bypassSecurityTrustUrl(headAvatarPath);
          }
          // Par défaut, utiliser la tête d'invité
          const avatarPath = 'assets/avatars/head_guest.svg';
          console.log('Generated avatar path:', avatarPath);
          return this.sanitizer.bypassSecurityTrustUrl(avatarPath);
        }),
        catchError((error) => {
          console.error('Error loading avatar:', error);
          return of(this.sanitizer.bypassSecurityTrustUrl('assets/svg/logo.svg'));
        })
      );

      this.userAvatarShape$ = this.authService.getCurrentUser().pipe(
        map(user => user.avatarShape || 'circle'),
        catchError(() => of('circle'))
      );

      this.userAvatarColor$ = this.authService.getCurrentUser().pipe(
        map(user => user.avatarColor || '#257D54'),
        catchError(() => of('#257D54'))
      );

      // Combine avatar shape et couleur pour créer le SVG coloré
      this.userAvatarSvg$ = combineLatest([
        this.authService.getCurrentUser().pipe(
          map(user => {
            console.log('User avatarShape from service:', user.avatarShape);
            if (user.avatarShape) {
              // Convertir directement la forme en tête d'avatar
              const headPath = this.getHeadAvatarFromShape(user.avatarShape);
              console.log('Generated head avatar path from shape:', headPath);
              return headPath;
            }
            console.log('No user avatarShape, using guest head');
            return 'assets/avatars/head_guest.svg';
          })
        ),
        this.userAvatarColor$
      ]).pipe(
        switchMap(([avatarPath, color]) => {
          console.log('Loading SVG with path:', avatarPath, 'and color:', color);
          return this.loadAvatarSvg(avatarPath, color);
        }),
        catchError((error) => {
          console.error('Error in userAvatarSvg$:', error);
          return of(this.sanitizer.bypassSecurityTrustHtml(''));
        })
      );

      this.isAdmin$ = this.authService.isAdmin();

      this.userCompanyId$ = this.authService.getCurrentUser().pipe(
        map(user => user.companyId || null),
        catchError(() => of(null))
      );
    } else if (this.authService.isGuest()) {
      this.userName$ = of('Invité');
      this.userAvatar$ = of(this.sanitizer.bypassSecurityTrustUrl('assets/avatars/head_guest.svg'));
      this.userAvatarShape$ = of('circle');
      this.userAvatarColor$ = of('#257D54');
      this.userAvatarSvg$ = this.loadAvatarSvg('assets/avatars/head_guest.svg', '#257D54');
      this.isAdmin$ = of(false);
      this.userCompanyId$ = of(null);
    } else {
      this.userName$ = of('Utilisateur');
      this.userAvatar$ = of(this.sanitizer.bypassSecurityTrustUrl('assets/avatars/head_guest.svg'));
      this.userAvatarShape$ = of('circle');
      this.userAvatarColor$ = of('#257D54');
      this.userAvatarSvg$ = this.loadAvatarSvg('assets/avatars/head_guest.svg', '#257D54');
      this.isAdmin$ = of(false);
      this.userCompanyId$ = of(null);
    }
  }

  logout() {
    this.authService.logout();
    this.loadUserData();
  }

  toggle() {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }

  close() {
    this.isMobileMenuOpen = false;
  }

  open() {
    return this.isMobileMenuOpen;
  }


}

