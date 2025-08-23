import { Component } from '@angular/core';
import {BackButtonComponent} from '../../components/back-button/back-button.component';
import {RouterLink} from '@angular/router';
import {SeoService} from '../../services/seo.service';
@Component({
  standalone: true,
  selector: 'app-about',
  imports: [
    BackButtonComponent,
    RouterLink
  ],
  templateUrl: './about.component.html',
  styleUrl: './about.component.scss'
})
export class AboutComponent {

  constructor(
    private readonly seoService: SeoService
  ) {}

  ngOnInit() {
    this.seoService.updateSEO({
      title: 'Blob - À propos de notre plateforme',
      description: 'Découvrez l’histoire de Blob, notre mission et notre équipe derrière la plateforme de quiz interactifs.',
      keywords: 'à propos, histoire, mission, équipe, Blob, quiz, éducation',
      ogTitle: 'À propos de Blob',
      ogDescription: 'Apprenez-en plus sur Blob, la plateforme qui rend l’apprentissage interactif et ludique.',
      ogUrl: '/a-propos'
    });
  }
}
