import { Component } from '@angular/core';
import { BackButtonComponent } from '../../components/back-button/back-button.component';
import { Router, RouterLink } from '@angular/router';

import { SeoService } from '../../services/seo.service';

@Component({
  standalone: true,
  selector: 'app-legal-notices',
  imports: [BackButtonComponent, RouterLink],
  templateUrl: './legal-notices.component.html',
  styleUrl: './legal-notices.component.scss',
})
export class LegalNoticesComponent {
  constructor(private readonly seoService: SeoService) {}

  ngOnInit(): void {
    this.seoService.updateSEO({
      title: 'Blob - Mentions légales',
      description:
        'Consultez les mentions légales de Blob, votre plateforme de quiz interactifs éducatifs.',
      keywords: 'mentions légales, conditions, politique, Blob, quiz, éducation',
      ogTitle: 'Mentions légales de Blob',
      ogDescription:
        'Informations légales concernant Blob, plateforme d’apprentissage et de quiz interactifs.',
      ogUrl: '/mentions-legales',
    });
  }
}
