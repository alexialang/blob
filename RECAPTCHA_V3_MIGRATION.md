# Migration reCAPTCHA v2 vers v3

## ✅ Modifications effectuées

### Frontend
- ✅ Suppression du widget reCAPTCHA v2 visible
- ✅ Implementation de l'exécution automatique en arrière-plan 
- ✅ Suppression du champ `recaptcha` du formulaire
- ✅ Ajout de la configuration centralisée dans `recaptcha.ts`

### Backend  
- ✅ Mise à jour de `verifyCaptcha()` pour gérer le score v3
- ✅ Validation de l'action pour éviter les attaques
- ✅ Score minimum configurable (actuellement 0.5)
- ✅ Logs détaillés pour le debugging

## 🔧 Configuration requise

### 1. Clés reCAPTCHA v3
Obtenez vos nouvelles clés sur : https://www.google.com/recaptcha/admin

### 2. Variables d'environnement Backend
```bash
# Dans votre .env
RECAPTCHA_SECRET_KEY=votre_cle_secrete_v3
```

### 3. Configuration Frontend
```typescript
// Dans front/src/environments/recaptcha.ts
export const recaptchaConfig = {
  siteKey: 'votre_cle_site_v3',
  actions: {
    register: 'register',
    login: 'login', 
    passwordReset: 'password_reset'
  }
};
```

## 🎯 Avantages de reCAPTCHA v3

- ✅ **Invisible** : Aucune interaction utilisateur requise
- ✅ **Score basé** : Détection plus fine des bots (0.0 = bot, 1.0 = humain)
- ✅ **Actions spécifiques** : Validation par type d'action
- ✅ **Meilleure UX** : Pas d'interruption du flux utilisateur

## ⚙️ Configuration du score

Dans `UserService.php`, vous pouvez ajuster le score minimum :

```php
// Score minimum accepté (entre 0.0 et 1.0)
if ($score >= 0.5) { // Ajustez selon vos besoins
    return true;
}
```

**Recommandations :**
- `0.9` : Très strict (peu de faux positifs, mais peut bloquer des humains)
- `0.5` : Équilibré (recommandé pour commencer)
- `0.3` : Permissif (laisse passer plus d'utilisateurs, mais plus de bots)

## 🚀 Pour activer

1. Remplacez `YOUR_SITE_KEY_V3_HERE` par votre vraie clé site
2. Configurez `RECAPTCHA_SECRET_KEY` dans votre .env backend
3. Testez l'inscription pour vérifier le fonctionnement

## 📊 Monitoring

Les logs incluent maintenant :
- Score reCAPTCHA reçu
- Action validée
- Résultat de la validation

Surveillez les logs pour ajuster le score si nécessaire.
