<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/stripe', name: 'api_stripe_')]
class StripeWebhookController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private string $stripeSecretKey,
        private string $stripeWebhookSecret,
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    #[Route('/webhook', name: 'webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $this->stripeWebhookSecret);
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('Invalid payload in Stripe webhook', ['error' => $e->getMessage()]);

            return new Response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            $this->logger->error('Invalid signature in Stripe webhook', ['error' => $e->getMessage()]);

            return new Response('Invalid signature', 400);
        }

        // Gérer les événements de paiement
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;
            default:
                $this->logger->info('Unhandled event type', ['type' => $event->type]);
        }

        return new Response('OK', 200);
    }

    private function handleCheckoutSessionCompleted($session): void
    {
        $this->logger->info('Checkout session completed', [
            'session_id' => $session->id,
            'payment_status' => $session->payment_status,
            'metadata' => $session->metadata,
        ]);

        // Ici vous pouvez ajouter la logique pour enregistrer le don en base de données
        // Par exemple, créer un enregistrement Donation avec les informations du paiement
    }

    private function handlePaymentIntentSucceeded($paymentIntent): void
    {
        $this->logger->info('Payment intent succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'metadata' => $paymentIntent->metadata,
        ]);
    }
}
