export const environment = {
  production: true,
  apiUrl: 'https://blob.dev.local',
  apiBaseUrl: 'https://blob.dev.local/api',
  analytics: {
    umamiUrl: 'https://analytics.blob.dev.local',
    umamiWebsiteId: 'your-website-id',
    enabled: true,
    respectPrivacy: true,
  },
  // Optimisations de performance
  enableTracing: false,
  enableProdMode: true,
  // Désactiver les logs en production
  logLevel: 'error',
};
