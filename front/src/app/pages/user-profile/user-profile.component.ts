import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router, RouterLink, ActivatedRoute } from '@angular/router';
import { UserService } from '../../services/user.service';
import { User, Badge } from '../../models/user.interface';
import { UserStatistics } from '../../models/user-statistics.interface';
import { AlertService } from '../../services/alert.service';
import { StatisticsChartsComponent } from '../../components/statistics-charts/statistics-charts.component';
import { AuthService } from '../../services/auth.service';
import { AnalyticsService } from '../../services/analytics.service';

@Component({
  selector: 'app-user-profile',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    RouterLink,
    StatisticsChartsComponent
  ],
  templateUrl: './user-profile.component.html',
  styleUrls: ['./user-profile.component.scss']
})
export class UserProfileComponent implements OnInit {
  private userService = inject(UserService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  private alertService = inject(AlertService);
  private fb = inject(FormBuilder);
  private authService = inject(AuthService);
  private analytics = inject(AnalyticsService);

  user: User | null = null;
  isLoading = false;
  isEditing = false;
  isAdmin = false;
  isOwnProfile = true;
  targetUserId: number | null = null;
  profileForm: FormGroup;
  userStatistics: UserStatistics | null = null;
  allBadges: Badge[] = [];
  isLoadingStatistics = false;

  constructor() {
    this.profileForm = this.fb.group({
      pseudo: ['', [Validators.required, Validators.minLength(3)]],
      firstName: ['', [Validators.required]],
      lastName: ['', [Validators.required]],
      email: ['', [Validators.required, Validators.email]],
      avatar: ['']
    });
  }

  ngOnInit() {
    if (!this.authService.isLoggedIn()) {
      this.router.navigate(['/connexion']);
      return;
    }

    this.analytics.trackProfileView();

    this.route.params.subscribe(params => {
      if (params['id']) {
        this.targetUserId = +params['id'];
        this.isOwnProfile = false;
        this.loadSpecificUserProfile(this.targetUserId);
      } else {
        this.isOwnProfile = true;
        this.loadUserProfile();
      }
      // Charger les statistiques après avoir défini isOwnProfile et targetUserId
      this.loadUserStatistics();
    });

    this.loadAllBadges();
    this.checkAdminStatus();
  }

  checkAdminStatus() {
    this.authService.isAdmin().subscribe(isAdmin => {
      this.isAdmin = isAdmin;
    });
  }

  loadUserProfile() {
    this.userService.getUserProfile().subscribe({
      next: (user) => {
        this.user = user;
        this.profileForm.patchValue({
          firstName: user.firstName || '',
          lastName: user.lastName || '',
          pseudo: user.pseudo || '',
          email: user.email || '',
          avatar: user.avatar || ''
        });
      },
      error: (error) => {
        if (error.status === 401) {
          // Rediriger vers la connexion si non authentifié
          this.authService.logout();
          this.router.navigate(['/connexion']);
        }
      }
    });
  }

  loadSpecificUserProfile(userId: number) {
    this.userService.getUserProfileById(userId).subscribe({
      next: (user: User) => {
        this.user = user;
        this.profileForm.patchValue({
          firstName: user.firstName || '',
          lastName: user.lastName || '',
          pseudo: user.pseudo || '',
          email: user.email || '',
          avatar: user.avatar || ''
        });
      },
      error: (error: any) => {
      }
    });
  }

  loadUserStatistics() {
    this.isLoadingStatistics = true;
    this.userStatistics = null; // Reset des statistiques pendant le chargement
    
    console.log('Chargement des statistiques - isOwnProfile:', this.isOwnProfile, 'targetUserId:', this.targetUserId);
    
    if (this.isOwnProfile) {
      this.userService.getUserStatistics().subscribe({
        next: (stats) => {
          console.log('Statistiques reçues pour profil personnel:', stats);
          this.userStatistics = stats;
          this.isLoadingStatistics = false;
        },
        error: (error) => {
          console.error('Erreur lors du chargement des statistiques personnelles:', error);
          this.isLoadingStatistics = false;
          if (error.status === 401) {
            this.authService.logout();
            this.router.navigate(['/connexion']);
          } else {
            this.alertService.error('Erreur lors du chargement des statistiques');
          }
        }
      });
    } else if (this.targetUserId) {
      this.userService.getUserStatisticsById(this.targetUserId).subscribe({
        next: (stats: UserStatistics) => {
          console.log('Statistiques reçues pour utilisateur:', this.targetUserId, stats);
          this.userStatistics = stats;
          this.isLoadingStatistics = false;
        },
        error: (error: any) => {
          console.error('Erreur lors du chargement des statistiques de l\'utilisateur:', error);
          this.isLoadingStatistics = false;
          this.alertService.error('Erreur lors du chargement des statistiques de l\'utilisateur');
        }
      });
    } else {
      console.warn('Impossible de charger les statistiques - conditions non remplies');
      this.isLoadingStatistics = false;
    }
  }

  loadAllBadges() {
    this.allBadges = [
      { id: 1, name: 'Premier Quiz', description: 'Complétez votre premier quiz', image: 'badge-first-quiz.svg' },
      { id: 2, name: 'Expert', description: 'Réussissez 10 quiz avec 80% de réussite', image: 'badge-expert.svg' },
      { id: 3, name: 'Créateur', description: 'Créez votre premier quiz', image: 'badge-creator.svg' },
      { id: 4, name: 'Social', description: 'Rejoignez un groupe', image: 'badge-social.svg' }
    ];
  }

  toggleEdit() {
    this.isEditing = !this.isEditing;
    if (this.isEditing && this.user) {
      this.profileForm.patchValue({
        firstName: this.user.firstName || '',
        lastName: this.user.lastName || '',
        pseudo: this.user.pseudo || '',
        email: this.user.email || '',
        avatar: this.user.avatar || ''
      });
    }
  }

  onSubmit() {
    if (this.profileForm.valid && this.user) {
      const updateData = this.profileForm.value;

      this.userService.updateUserProfile(updateData).subscribe({
        next: (updatedUser) => {
          this.user = updatedUser;
          this.isEditing = false;
          this.alertService.success('Profil mis à jour avec succès!');
        },
        error: (error) => {
          this.alertService.error('Erreur lors de la mise à jour du profil.');
        }
      });
    }
  }

  getUserBadges(): Badge[] {
    if (this.user?.badges && this.user.badges.length > 0) {
      return this.user.badges;
    }
    return [];
  }

  getAvailableBadges(): Badge[] {
    const userBadgeIds = this.getUserBadges().map((badge: Badge) => badge.id);
    return this.allBadges.filter((badge: Badge) => !userBadgeIds.includes(badge.id));
  }

  getDisplayName(): string {
    if (this.user?.pseudo) return this.user.pseudo;
    return `${this.user?.firstName} ${this.user?.lastName}`;
  }

  getUserAvatarShape(): string {
    return this.user?.avatarShape || 'blob_circle';
  }

  getUserAvatarColor(): string {
    return this.user?.avatarColor || '#257D54';
  }
}
