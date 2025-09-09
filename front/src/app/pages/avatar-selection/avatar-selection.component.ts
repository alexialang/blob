
import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { UserService } from '../../services/user.service';
import { User } from '../../models/user.interface';
import {BackButtonComponent} from '../../components/back-button/back-button.component';

@Component({
  selector: 'app-avatar-selection',
  standalone: true,
  imports: [CommonModule, BackButtonComponent],
  templateUrl: './avatar-selection.component.html',
  styleUrls: ['./avatar-selection.component.scss']
})
export class AvatarSelectionComponent implements OnInit {
  private userService = inject(UserService);
  private router = inject(Router);

  user: User | null = null;
  isLoading = false;

  availableShapes = ['blob_flower', 'blob_circle', 'blob_pic', 'blob_wave'];
  selectedShapeIndex = 0;

  colors = ['#257D54', '#91DEDA', '#FAA24B', '#D30D4C'];
  selectedColor = this.colors[0];

  ngOnInit() {

    this.loadUserProfile();
  }

  loadUserProfile() {
    this.userService.getUserProfile().subscribe({
      next: user => {
        this.user = user;
        const shapeId = user.avatarShape ?? this.availableShapes[0];
        const shapeIndex = this.availableShapes.indexOf(shapeId);
        this.selectedShapeIndex = shapeIndex >= 0 ? shapeIndex : 0;

        this.selectedColor = user.avatarColor ?? this.colors[0];
      },
      error: error => {
        this.router.navigate(['/profil']);
      }
    });
  }

  prevShape() {
    this.selectedShapeIndex =
      (this.selectedShapeIndex - 1 + this.availableShapes.length) % this.availableShapes.length;
  }
  nextShape() {
    this.selectedShapeIndex =
      (this.selectedShapeIndex + 1) % this.availableShapes.length;
  }

  selectColor(color: string) {
    this.selectedColor = color;
  }

  saveAvatar() {
    if (!this.user) return;
    this.isLoading = true;

    const selectedShape = this.availableShapes[this.selectedShapeIndex];

    this.userService.updateAvatar({
      shape: selectedShape,
      color: this.selectedColor
    }).subscribe({
      next: updatedUser => {
        this.user = updatedUser;
        this.isLoading = false;
        this.router.navigate(['/profil']);
      },
      error: err => {
        this.isLoading = false;
        console.error('Erreur lors de la sauvegarde de l\'avatar:', err);
      }
    });
  }
}
