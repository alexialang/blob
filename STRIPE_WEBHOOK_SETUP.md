# Configuration des Webhooks Stripe

## Vue d'ensemble

Ce document explique comment configurer les webhooks Stripe pour gérer les retours de paiement dans l'application Blob.

## Configuration requise

### Variables d'environnement

Ajoutez les variables suivantes à votre fichier `.env` :

```env
# Clé secrète Stripe (déjà existante)
STRIPE_SECRET_KEY=sk_test_...

# Nouvelle variable pour le webhook
STRIPE_WEBHOOK_SECRET=whsec_...

# URL frontend (déjà existante)
APP_FRONTEND_URL=https://votre-domaine.com
```

### Configuration du webhook dans Stripe Dashboard

1. Connectez-vous à votre [Stripe Dashboard](https://dashboard.stripe.com)
2. Allez dans **Développeurs** > **Webhooks**
3. Cliquez sur **Ajouter un endpoint**
4. Configurez l'endpoint :
   - **URL de l'endpoint** : `https://votre-domaine.com/api/stripe/webhook`
   - **Événements à écouter** :
     - `checkout.session.completed`
     - `payment_intent.succeeded`
5. Copiez la **Clé secrète du webhook** et ajoutez-la à votre `.env`

## Fonctionnalités implémentées

### 1. Redirection après paiement

- **URL de succès** : `/payment-success?session_id={CHECKOUT_SESSION_ID}`
- **URL d'annulation** : `/donation?cancelled=true`

### 2. Page de confirmation

- Affichage des détails du paiement
- Message de remerciement personnalisé
- Redirection automatique vers l'accueil
- Boutons d'action (retour accueil, nouveau don)

### 3. Gestion des erreurs

- Gestion des annulations de paiement
- Messages d'erreur appropriés
- Logging des événements Stripe

## Structure des fichiers

### Backend

- `back/src/Controller/StripeWebhookController.php` - Gestion des webhooks
- `back/src/Service/PaymentService.php` - Service de paiement mis à jour
- `back/config/services.yaml` - Configuration des services

### Frontend

- `front/src/app/pages/payment-success/` - Page de confirmation
- `front/src/app/pages/donation/donation.component.ts` - Gestion des retours
- `front/src/app/app.routes.ts` - Route de confirmation

## Test en local

Pour tester en local, utilisez [ngrok](https://ngrok.com/) ou un service similaire :

1. Exposez votre serveur local :
   ```bash
   ngrok http 8000
   ```

2. Configurez l'URL webhook dans Stripe :
   ```
   https://votre-ngrok-url.ngrok.io/api/stripe/webhook
   ```

3. Testez le flux complet de paiement

## Sécurité

- Vérification de la signature Stripe dans le webhook
- Validation des données de paiement
- Logging des événements pour le debugging

## Support

En cas de problème, vérifiez :
1. Les logs du serveur pour les erreurs de webhook
2. La configuration des URLs dans Stripe Dashboard
3. Les variables d'environnement
4. La connectivité réseau entre Stripe et votre serveur
