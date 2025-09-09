import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

// Métriques personnalisées
export let errorCounter = new Counter('errors');
export let errorRate = new Rate('error_rate');
export let loginDuration = new Trend('login_duration');
export let quizDuration = new Trend('quiz_duration');

// Configuration des tests
export let options = {
  stages: [
    { duration: '2m', target: 10 }, // Montée progressive
    { duration: '5m', target: 10 }, // Maintien de la charge
    { duration: '2m', target: 20 }, // Augmentation de la charge
    { duration: '5m', target: 20 }, // Maintien de la charge élevée
    { duration: '2m', target: 0 },  // Descente progressive
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% des requêtes doivent être < 500ms
    http_req_failed: ['rate<0.1'],    // Moins de 10% d'erreurs
    error_rate: ['rate<0.1'],         // Moins de 10% d'erreurs métier
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';
const API_URL = `${BASE_URL}/api`;

// Données de test
const testUsers = [
  { email: 'user1@test.com', password: 'password123' },
  { email: 'user2@test.com', password: 'password123' },
  { email: 'user3@test.com', password: 'password123' },
];

export default function() {
  // Sélection aléatoire d'un utilisateur de test
  const user = testUsers[Math.floor(Math.random() * testUsers.length)];
  
  // Test du workflow complet
  performLoginFlow(user);
  sleep(1);
  performQuizFlow();
  sleep(1);
  performLogout();
  
  sleep(Math.random() * 2 + 1); // Pause aléatoire entre 1-3 secondes
}

function performLoginFlow(user) {
  // Test de la page de connexion
  let response = http.get(`${BASE_URL}/connexion`);
  check(response, {
    'Login page loads': (r) => r.status === 200,
    'Login page contains form': (r) => r.body.includes('form'),
  }) || errorCounter.add(1);

  // Test de l'API de connexion
  const loginStart = Date.now();
  
  response = http.post(`${API_URL}/login`, {
    email: user.email,
    password: user.password,
  }, {
    headers: { 'Content-Type': 'application/json' },
  });

  const loginTime = Date.now() - loginStart;
  loginDuration.add(loginTime);

  const loginSuccess = check(response, {
    'Login API success': (r) => r.status === 200,
    'Login returns token': (r) => {
      try {
        const body = JSON.parse(r.body);
        return body.token !== undefined;
      } catch (e) {
        return false;
      }
    },
  });

  if (!loginSuccess) {
    errorCounter.add(1);
    errorRate.add(1);
    return null;
  }

  // Extraction du token pour les requêtes suivantes
  let token = null;
  try {
    const body = JSON.parse(response.body);
    token = body.token;
  } catch (e) {
    errorCounter.add(1);
    return null;
  }

  return token;
}

function performQuizFlow() {
  const token = performLoginFlow(testUsers[0]);
  if (!token) return;

  const headers = {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  };

  // Test de récupération des quiz
  let response = http.get(`${API_URL}/quiz`, { headers });
  check(response, {
    'Quiz list loads': (r) => r.status === 200,
    'Quiz list contains data': (r) => {
      try {
        const quizzes = JSON.parse(r.body);
        return Array.isArray(quizzes) && quizzes.length > 0;
      } catch (e) {
        return false;
      }
    },
  }) || errorCounter.add(1);

  // Sélection d'un quiz aléatoire
  let quizId = 1;
  try {
    const quizzes = JSON.parse(response.body);
    if (quizzes.length > 0) {
      quizId = quizzes[Math.floor(Math.random() * quizzes.length)].id;
    }
  } catch (e) {
    errorCounter.add(1);
    return;
  }

  // Test de démarrage d'un quiz
  const quizStart = Date.now();
  
  response = http.get(`${API_URL}/quiz/${quizId}`, { headers });
  check(response, {
    'Quiz details load': (r) => r.status === 200,
    'Quiz has questions': (r) => {
      try {
        const quiz = JSON.parse(r.body);
        return quiz.questions && quiz.questions.length > 0;
      } catch (e) {
        return false;
      }
    },
  }) || errorCounter.add(1);

  // Simulation de réponses aux questions
  let quiz;
  try {
    quiz = JSON.parse(response.body);
  } catch (e) {
    errorCounter.add(1);
    return;
  }

  if (quiz.questions && quiz.questions.length > 0) {
    // Répondre à la première question
    const question = quiz.questions[0];
    const answer = question.answers ? question.answers[0] : null;
    
    if (answer) {
      response = http.post(`${API_URL}/quiz/${quizId}/answer`, {
        questionId: question.id,
        answerIds: [answer.id],
        timeSpent: Math.floor(Math.random() * 30) + 10, // 10-40 secondes
      }, { headers });

      check(response, {
        'Answer submission success': (r) => r.status === 200,
        'Answer response contains feedback': (r) => {
          try {
            const body = JSON.parse(r.body);
            return body.isCorrect !== undefined;
          } catch (e) {
            return false;
          }
        },
      }) || errorCounter.add(1);
    }
  }

  const quizTime = Date.now() - quizStart;
  quizDuration.add(quizTime);

  // Test de finalisation du quiz
  response = http.post(`${API_URL}/quiz/${quizId}/complete`, {
    totalScore: Math.floor(Math.random() * 100),
    timeSpent: quizTime,
    correctAnswers: Math.floor(Math.random() * 10),
    totalQuestions: 10,
  }, { headers });

  check(response, {
    'Quiz completion success': (r) => r.status === 200,
  }) || errorCounter.add(1);
}

function performLogout() {
  const response = http.post(`${API_URL}/logout`);
  check(response, {
    'Logout success': (r) => r.status === 200 || r.status === 204,
  }) || errorCounter.add(1);
}

// Test de stress spécifique pour l'API
export function stressTest() {
  const response = http.get(`${API_URL}/health`);
  check(response, {
    'Health check passes': (r) => r.status === 200,
  });
}

// Test de charge pour les WebSockets (multiplayer)
export function multiplayerStressTest() {
  const token = performLoginFlow(testUsers[0]);
  if (!token) return;

  const headers = {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  };

  // Création d'une room multiplayer
  let response = http.post(`${API_URL}/multiplayer/rooms`, {
    quizId: 1,
    maxPlayers: 10,
    isPrivate: false,
  }, { headers });

  check(response, {
    'Multiplayer room creation': (r) => r.status === 201,
  }) || errorCounter.add(1);

  // Connexion à la room
  if (response.status === 201) {
    try {
      const room = JSON.parse(response.body);
      
      response = http.post(`${API_URL}/multiplayer/rooms/${room.id}/join`, {}, { headers });
      check(response, {
        'Join multiplayer room': (r) => r.status === 200,
      }) || errorCounter.add(1);
      
    } catch (e) {
      errorCounter.add(1);
    }
  }
}

// Configuration pour différents types de tests
export let scenarios = {
  // Test de charge normal
  normal_load: {
    executor: 'constant-vus',
    vus: 10,
    duration: '5m',
    exec: 'default',
  },
  
  // Test de stress
  stress_test: {
    executor: 'ramping-vus',
    startVUs: 0,
    stages: [
      { duration: '2m', target: 50 },
      { duration: '5m', target: 50 },
      { duration: '2m', target: 100 },
      { duration: '5m', target: 100 },
      { duration: '2m', target: 0 },
    ],
    exec: 'stressTest',
  },
  
  // Test multiplayer
  multiplayer_test: {
    executor: 'constant-vus',
    vus: 5,
    duration: '3m',
    exec: 'multiplayerStressTest',
  },
};

