import { bootstrapApplication } from '@angular/platform-browser';
import { AppComponent } from './app/app.component';
import { appConfig } from './app/app.config';
import { environment } from './environments/environment';
import 'zone.js';

// Optimisations de performance
if (environment.production) {
  // Désactiver les logs en production
  console.log = () => {};
  console.warn = () => {};
  console.info = () => {};

  // Désactiver les traces Angular
  // if (environment.enableTracing === false) {
    // Supprimer les traces de performance
  // }
}

bootstrapApplication(AppComponent, appConfig).catch(err => console.error(err));
