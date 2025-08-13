import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { UserService } from '../../services/user.service';
import { User, Badge } from '../../models/user.interface';
import { UserStatistics } from '../../models/user-statistics.interface';
import { BackButtonComponent } from '../../components/back-button/back-button.component';
import { AlertService } from '../../services/alert.service';
import { StatisticsChartsComponent } from '../../components/statistics-charts/statistics-charts.component';

@Component({
  selector: 'app-user-profile',
  standalone: true,
  imports: [
    CommonModule, 
    FormsModule, 
    ReactiveFormsModule, 
    RouterLink, 
    BackButtonComponent, 
    StatisticsChartsComponent
  ],
  templateUrl: './user-profile.component.html',
  styleUrls: ['./user-profile.component.scss']
})
export class UserProfileComponent implements OnInit {
  private userService = inject(UserService);
  private router = inject(Router);
  private alertService = inject(AlertService);
  private fb = inject(FormBuilder);

  user: User | null = null;
  isLoading = false;
  isEditing = false;
  editForm = {
    firstName: '',
    lastName: '',
    pseudo: ''
  };
  profileForm: FormGroup;
  userStatistics: UserStatistics | null = null;
  allBadges: Badge[] = [];

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
    this.loadUserProfile();
    this.loadUserStatistics();
    this.loadAllBadges();
  }

  loadUserProfile() {
    this.userService.getUserProfile().subscribe({
      next: (user) => {
        this.user = user;
        this.editForm = {
          firstName: user.firstName,
          lastName: user.lastName,
          pseudo: user.pseudo || ''
        };
      },
      error: (error) => {
        console.error('Erreur lors du chargement du profil utilisateur:', error);
      }
    });
  }

  loadUserStatistics() {
    this.userService.getUserStatistics().subscribe({
      next: (stats) => {
        this.userStatistics = stats;
      },
      error: (error) => {
        console.error('Erreur lors du chargement des statistiques:', error);
      }
    });
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
      this.editForm = {
        firstName: this.user.firstName || '',
        lastName: this.user.lastName || '',
        pseudo: this.user.pseudo || ''
      };
    }
  }

  onSubmit() {
    if (this.user) {
      const updateData = this.editForm;

      this.userService.updateUserProfile(updateData).subscribe({
        next: (updatedUser) => {
          this.user = updatedUser;
          this.isEditing = false;
          this.alertService.success('Profil mis à jour avec succès!');
        },
        error: (error) => {
          console.error('Erreur lors de la mise à jour du profil:', error);
          this.alertService.error('Erreur lors de la mise à jour du profil.');
        }
      });
    }
  }

  getUserBadges(): Badge[] {
    return this.user?.badges || [];
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
