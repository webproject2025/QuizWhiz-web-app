import { fetchQuestions, getUniqueCategories, filterQuestions } from './data.js';
import { Timer } from './timer.js';
import {
    elements,
    populateCategoryDropdown,
    showScreen,
    displayQuestion,
    updateScoreDisplay,
    showAnswerFeedback,
    showExplanation,
    applyTheme,
    loadTheme,
    displayResults,
    updateLeaderboard,
    displayChart
} from './ui.js';

// --- State Variables ---
let currentQuestions = [];
let currentQuestionIndex = 0;
let score = 0;
let username = 'Guest';
let selectedAnswers = [];
let quizStartTime;
let questionTimer = null;

// --- Utility Functions ---
function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
}

// --- Core Quiz Logic ---
function startQuiz() {
    console.log("Starting quiz...");
    username = elements.usernameInput.value.trim() || 'Guest';
    localStorage.setItem('quizUsername', username);
    elements.quizUsername.textContent = username;

    const selectedCategory = elements.categorySelect.value;
    const selectedDifficulty = elements.difficultySelect.value;

    console.log(`Filters - Category: ${selectedCategory}, Difficulty: ${selectedDifficulty}`);

    fetchQuestions().then(allQuestions => {
        currentQuestions = filterQuestions(allQuestions, selectedCategory, selectedDifficulty);

        if (currentQuestions.length === 0) {
            alert("No questions found for the selected criteria. Please try different options.");
            return;
        }

        shuffleArray(currentQuestions);
        console.log("Filtered & shuffled questions:", currentQuestions);

        currentQuestionIndex = 0;
        score = 0;
        selectedAnswers = [];
        updateScoreDisplay(score);

        showScreen('quiz-screen');
        displayQuestion(currentQuestions[currentQuestionIndex], currentQuestionIndex, currentQuestions.length);
        quizStartTime = Date.now();

        questionTimer = new Timer(15, elements.timeLeft, handleTimeOut);
        questionTimer.start();

    }).catch(error => {
        console.error("Failed to initialize quiz:", error);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.innerHTML = `
            <p>⚠️ Could not load quiz data</p>
            <button onclick="location.reload()">Try Again</button>
        `;
        elements.startScreen.prepend(errorDiv);
    });
}

function handleAnswerSelection(event) {
    const selectedButton = event.target.closest('.option-btn');
    if (!selectedButton || selectedButton.disabled) return;

    questionTimer.stop();

    const selectedAnswer = selectedButton.dataset.answer;
    const currentQuestion = currentQuestions[currentQuestionIndex];
    const correctAnswer = currentQuestion.correctAnswer;
    const isCorrect = selectedAnswer === correctAnswer;

    selectedAnswers.push({
        question: currentQuestion.question,
        options: currentQuestion.options,
        userAnswer: selectedAnswer,
        correctAnswer,
        explanation: currentQuestion.explanation
    });

    if (isCorrect) {
        score++;
        updateScoreDisplay(score);
    }

    showAnswerFeedback(correctAnswer, selectedButton);
    showExplanation(currentQuestion.explanation);

    if (currentQuestionIndex >= currentQuestions.length - 1) {
        elements.nextQuestionBtn.textContent = 'Finish Quiz';
    }
}

function handleNextQuestion() {
    currentQuestionIndex++;
    if (currentQuestionIndex < currentQuestions.length) {
        elements.nextQuestionBtn.textContent = 'Next';
        displayQuestion(currentQuestions[currentQuestionIndex], currentQuestionIndex, currentQuestions.length);
        questionTimer.reset(15);
        questionTimer.start();
    } else {
        console.log("Quiz finished!");
        endQuiz();
    }
}

function endQuiz() {
    const timeTaken = Math.round((Date.now() - quizStartTime) / 1000);
    const totalQuestions = currentQuestions.length;
    const accuracy = totalQuestions > 0 ? Math.round((score / totalQuestions) * 100) : 0;
    const category = elements.categorySelect.value;

    displayResults(username, score, totalQuestions, timeTaken, category);
    updateLeaderboard(score, category);
    displayChart(score, totalQuestions - score);

    localStorage.setItem('lastQuizResults', JSON.stringify({
        username,
        score,
        accuracy,
        timeTaken,
        totalQuestions,
        category,
        difficulty: elements.difficultySelect.value,
        answeredQuestions: selectedAnswers
    }));

    showScreen('results-screen');
}

function handleTimeOut() {
    selectedAnswers.push({
        question: currentQuestions[currentQuestionIndex].question,
        userAnswer: null,
        correctAnswer: currentQuestions[currentQuestionIndex].correctAnswer,
        explanation: currentQuestions[currentQuestionIndex].explanation
    });

    showAnswerFeedback(currentQuestions[currentQuestionIndex].correctAnswer, null);
    showExplanation(currentQuestions[currentQuestionIndex].explanation);

    setTimeout(handleNextQuestion, 1500);
}

// --- Event Listeners ---
function setupEventListeners() {
    elements.startQuizBtn.addEventListener('click', startQuiz);
    elements.optionsContainer.addEventListener('click', handleAnswerSelection);
    elements.nextQuestionBtn.addEventListener('click', handleNextQuestion);

    elements.themeToggleBtn.addEventListener('click', () => {
        const currentTheme = document.body.classList.contains('dark-mode') ? 'light-mode' : 'dark-mode';
        applyTheme(currentTheme);
    });

    elements.playAgainBtn.addEventListener('click', () => {
        elements.categorySelect.value = 'any';
        elements.difficultySelect.value = 'any';
        showScreen('start-screen');
    });

    elements.reviewAnswersBtn.addEventListener('click', () => {
        window.location.href = 'review.html';
    });

    const savedUsername = localStorage.getItem('quizUsername');
    if (savedUsername) {
        elements.usernameInput.value = savedUsername;
    }
}

// --- App Initialization ---
async function initializeApp() {
    console.log("Initializing QuizWhiz+...");
    loadTheme();
    setupEventListeners();
    showScreen('start-screen');

    try {
        const questions = await fetchQuestions();
        const categories = getUniqueCategories(questions);
        populateCategoryDropdown(categories);
        console.log("App initialized successfully.");
    } catch (error) {
        console.error("Initialization failed:", error);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.innerHTML = `
            <p>⚠️ Could not load quiz data</p>
            <button onclick="location.reload()">Try Again</button>
        `;
        elements.startScreen.prepend(errorDiv);
    }
}

// --- Start the app ---
initializeApp();
