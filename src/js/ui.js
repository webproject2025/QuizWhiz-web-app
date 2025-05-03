// DOM Element Selectors
export const elements = {
    // App Containers
    appContainer: document.getElementById('app-container'),
    startScreen: document.getElementById('start-screen'),
    quizScreen: document.getElementById('quiz-screen'),
    resultsScreen: document.getElementById('results-screen'),
    
    // Start Screen Elements
    usernameInput: document.getElementById('username-input'),
    categorySelect: document.getElementById('category-select'),
    difficultySelect: document.getElementById('difficulty-select'),
    startQuizBtn: document.getElementById('start-quiz-btn'),
    themeToggleBtn: document.getElementById('theme-toggle-btn'),
    
    // Quiz Screen Elements
    quizUsername: document.getElementById('quiz-username'),
    quizScore: document.getElementById('quiz-score'),
    timeLeft: document.getElementById('time-left'),
    progressBar: document.getElementById('progress-bar'),
    questionText: document.getElementById('question-text'),
    optionsContainer: document.getElementById('options-container'),
    explanationText: document.getElementById('explanation-text'),
    nextQuestionBtn: document.getElementById('next-question-btn'),
    
    // Results Screen Elements
    resultsUsername: document.getElementById('results-username'),
    finalScore: document.getElementById('final-score'),
    accuracy: document.getElementById('accuracy'),
    timeTaken: document.getElementById('time-taken'),
    highscoreMessage: document.getElementById('highscore-message'),
    leaderboardContainer: document.getElementById('leaderboard-container'),
    leaderboardCategory: document.getElementById('leaderboard-category'),
    leaderboardList: document.getElementById('leaderboard-list'),
    resultsChart: document.getElementById('results-chart'),
    reviewAnswersBtn: document.getElementById('review-answers-btn'),
    playAgainBtn: document.getElementById('play-again-btn')
};

// Populate category dropdown with available categories
export function populateCategoryDropdown(categories) {
    elements.categorySelect.innerHTML = '<option value="any">Any Category</option>';
    categories.sort().forEach(category => {
        const option = document.createElement('option');
        option.value = category;
        option.textContent = category;
        elements.categorySelect.appendChild(option);
    });
}

// Show specific screen and hide others
export function showScreen(screenId) {
    document.querySelectorAll('.quiz-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(screenId).classList.add('active');
}

// Update progress bar based on current question
export function updateProgressBar(currentQuestionIndex, totalQuestions) {
    const progressPercent = ((currentQuestionIndex + 1) / totalQuestions) * 100;
    elements.progressBar.style.width = `${progressPercent}%`;
}

// Display question and options
export function displayQuestion(question, questionIndex, totalQuestions) {
    elements.questionText.textContent = `Q${questionIndex + 1}: ${question.question}`;
    elements.optionsContainer.innerHTML = '';
    elements.explanationText.style.display = 'none';
    elements.nextQuestionBtn.style.display = 'none';

    question.options.forEach(option => {
        const button = document.createElement('button');
        button.classList.add('option-btn');
        button.textContent = option;
        button.dataset.answer = option;
        elements.optionsContainer.appendChild(button);
    });

    updateProgressBar(questionIndex, totalQuestions);
}

// Show explanation for current question
export function showExplanation(explanation) {
    if (explanation) {
        elements.explanationText.textContent = explanation;
        elements.explanationText.style.display = 'block';
    }
}

// Update score display
export function updateScoreDisplay(score) {
    elements.quizScore.textContent = score;
}

// Show visual feedback for answers
export function showAnswerFeedback(correctAnswer, selectedButton) {
    const buttons = elements.optionsContainer.querySelectorAll('.option-btn');
    
    buttons.forEach(button => {
        button.disabled = true;
        const isCorrect = button.dataset.answer === correctAnswer;
        
        if (isCorrect) {
            button.classList.add('correct');
        } else if (button === selectedButton) {
            button.classList.add('incorrect');
        }
    });

    elements.nextQuestionBtn.style.display = 'block';
}

// Apply theme to the application
export function applyTheme(theme) {
    document.body.className = theme;
    localStorage.setItem('quizTheme', theme);
    elements.themeToggleBtn.textContent = theme === 'dark-mode' ? '☀️' : '🌙';
}

// Load saved theme from localStorage
export function loadTheme() {
    const savedTheme = localStorage.getItem('quizTheme') || 'light-mode';
    applyTheme(savedTheme);
}

// Display quiz results
export function displayResults(username, score, totalQuestions, timeTaken, category) {
    elements.resultsUsername.textContent = username;
    elements.finalScore.textContent = score;
    elements.accuracy.textContent = `${Math.round((score / totalQuestions) * 100)}%`;
    elements.timeTaken.textContent = `${timeTaken}s`;
    elements.leaderboardCategory.textContent = category === 'any' ? 'All Categories' : category;
}

// Update and display leaderboard
export function updateLeaderboard(score, category) {
    const leaderboard = JSON.parse(localStorage.getItem('leaderboard') || '[]');
    const newEntry = {
        username: localStorage.getItem('quizUsername') || 'Guest',
        score,
        category,
        date: new Date().toLocaleString()
    };

    leaderboard.push(newEntry);
    
    // Sort by score (descending) and date (ascending)
    const sortedLeaderboard = leaderboard.sort((a, b) => {
        if (b.score !== a.score) return b.score - a.score;
        return new Date(a.date) - new Date(b.date);
    });

    // Update UI
    elements.leaderboardList.innerHTML = sortedLeaderboard
        .slice(0, 10)
        .map((entry, index) => `
            <li>
                ${index + 1}. ${entry.username} - ${entry.score}
                <span>${entry.date}</span>
            </li>
        `).join('');

    // Save to localStorage
    localStorage.setItem('leaderboard', JSON.stringify(sortedLeaderboard.slice(0, 10)));
}

// Display results chart using Chart.js
export function displayChart(correct, incorrect) {
    const ctx = elements.resultsChart.getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Correct', 'Incorrect'],
            datasets: [{
                data: [correct, incorrect],
                backgroundColor: [
                    getComputedStyle(document.documentElement).getPropertyValue('--success-color'),
                    getComputedStyle(document.documentElement).getPropertyValue('--danger-color')
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}