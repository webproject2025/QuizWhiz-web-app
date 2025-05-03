import { elements, loadTheme } from './ui.js';

export function loadReviewData() {
    const results = JSON.parse(localStorage.getItem('lastQuizResults'));
    if (!results) {
        elements.reviewList.innerHTML = '<p>No review data available.</p>';
        return;
    }

    elements.reviewList.innerHTML = results.answeredQuestions.map((q, index) => `
        <div class="review-item fade-in">
            <h3>Question ${index + 1}: ${q.question}</h3>
            <div class="options-review">
                ${q.options.map(opt => `
                    <div class="option 
                        ${opt === q.correctAnswer ? 'correct' : ''}
                        ${opt === q.userAnswer && opt !== q.correctAnswer ? 'incorrect' : ''}">
                        ${opt}
                        ${opt === q.userAnswer ? '<span class="user-choice">(Your Choice)</span>' : ''}
                    </div>
                `).join('')}
            </div>
            <p class="explanation">${q.explanation}</p>
        </div>
    `).join('');
}

function setupEventListeners() {
    document.getElementById('return-home').addEventListener('click', () => {
        window.location.href = 'index.html';
    });

    document.getElementById('theme-toggle-btn').addEventListener('click', () => {
        const currentTheme = document.body.classList.contains('dark-mode') 
            ? 'light-mode' : 'dark-mode';
        document.body.className = currentTheme;
        localStorage.setItem('quizTheme', currentTheme);
    });
}

loadTheme();
setupEventListeners();
loadReviewData();