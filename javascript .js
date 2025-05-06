// this is your js /// "// script.js --------somehow  Enhanced Quiz Review Functionality

// 1. Quiz Data Storage (Updated with time tracking)
const quizResults = {
    scorePercentage: 75,
    correctCount: 3,
    userAnswers: ["Paris", "Blue Whale", "Jupiter", "William Shakespeare"],
    timeSpent: [15, 22, 18, 25] // Seconds per question
};
// quizdata//////
const quizData = {     questions: [
    {
        question: "What is the capital of France?",
        answers: ["London", "Berlin", "Madrid", "Paris"],
        correctAnswer: "Paris",
        explanation: "Paris has been France's capital since the 5th century.",
        category: "Geography"
    },
    {
        question: "Which is the largest mammal?",
        answers: ["Elephant", "Blue Whale", "Giraffe", "Polar Bear"],
        correctAnswer: "Blue Whale",
        explanation: "Blue whales reach 100 feet long and weigh 200 tons.",
        category: "Science"
    },
    {
        question: "Which planet is known as the Red Planet?",
        answers: ["Venus", "Mars", "Jupiter", "Saturn"],
        correctAnswer: "Mars",
        explanation: "Mars appears red due to iron oxide (rust) on its surface.",
        category: "Science"
    },
    {
        question: "Who wrote 'Romeo and Juliet'?",
        answers: ["Charles Dickens", "Mark Twain", "William Shakespeare", "Jane Austen"],
        correctAnswer: "William Shakespeare",
        explanation: "Shakespeare wrote this play in the late 16th century.",
        category: "Literature"
    }
] };

// 2. Main Initialization
document.addEventListener('DOMContentLoaded', () => {
    displayScore();
    renderQuestions();
    renderCharts();
    showTimeStatistics();
    setupButtons();
    initSocialShare();
});

// 3. Core Functions (Updated)
function displayScore() {
    document.getElementById('final-score').textContent = quizResults.scorePercentage;
    document.getElementById('correct-count').textContent = quizResults.correctCount;
    document.getElementById('total-questions').textContent = quizData.questions.length; }

function renderQuestions() {    
    const container = document.getElementById('questions-review');
    container.innerHTML = quizData.questions.map((question, index) => `
        <div class="question-review ${quizResults.userAnswers[index] === question.correctAnswer ? 'correct' : 'incorrect'}">
            <div class="question-header">
                <h3>Question ${index + 1}</h3>
                <span class="status-badge">
                    ${quizResults.userAnswers[index] === question.correctAnswer ? '✓ Correct' : '✗ Incorrect'}
                </span>
            </div>
            <p class="question-text">${question.question}</p>
            <div class="answers-container">
                ${question.answers.map(answer => `
                    <div class="answer 
                        ${answer === question.correctAnswer ? 'correct-answer' : ''}
                        ${answer === quizResults.userAnswers[index] && answer !== question.correctAnswer ? 'user-answer' : ''}">
                        ${answer}
                    </div>
                `).join('')}
            </div>
            ${question.explanation ? `
                <div class="explanation">
                    <h4>Explanation:</h4>
                    <p>${question.explanation}</p>
                </div>
            ` : ''}
        </div>
    `).join('');
}

function initPDFExport() {
    const pdfBtn = document.createElement('button');
    pdfBtn.className = 'btn';
    pdfBtn.id = 'export-pdf';
    pdfBtn.textContent = 'Export PDF';
    
    pdfBtn.addEventListener('click', () => {
        // Use the globally available jsPDF from CDN
        const doc = new window.jspdf.jsPDF();
        
        // PDF Styling
        const title = 'QuizWhiz+ Results Report';
        const textX = 15;
        let textY = 20;
        
        // Header
        doc.setFontSize(22);
        doc.text(title, textX, textY);
        textY += 15;
        
        // Score Summary
        doc.setFontSize(14);
        doc.text(`Final Score: ${quizResults.scorePercentage}%`, textX, textY);
        textY += 10;
        doc.text(`Correct Answers: ${quizResults.correctCount}/${quizData.questions.length}`, textX, textY);
        textY += 15;
        
        // Questions Breakdown
        doc.setFontSize(16);
        doc.text('Question Breakdown:', textX, textY);
        textY += 10;
        
        quizData.questions.forEach((q, i) => {
            doc.setFontSize(12);
            doc.text(`Q${i + 1}: ${q.question}`, textX, textY);
            textY += 7;
            doc.setTextColor(quizResults.userAnswers[i] === q.correctAnswer ? '#4CAF50' : '#F44336');
            doc.text(`Your Answer: ${quizResults.userAnswers[i]}`, textX + 5, textY);
            doc.setTextColor('#000000');
            doc.text(`Correct Answer: ${q.correctAnswer}`, textX + 5, textY + 5);
            textY += 15;
            
            if (textY > 250) {
                doc.addPage();
                textY = 20;
            }

            
        });
        
        doc.save('quizwhiz-results.pdf');
        doc.text('Quiz Results', 10, 10);
        doc.save('quiz-results.pdf');
    });
}
    

 

// 7. Updated Button Setup
function setupButtons() {
    document.getElementById('retry-quiz').addEventListener('click', () => {
         window.location.href = 'quiz.html'; // Uncomment for real use
        console.log("Retry quiz clicked");
    });

    document.getElementById('new-quiz').addEventListener('click', () => {
        sessionStorage.clear();
         window.location.href = 'index.html';
        console.log("New quiz clicked");
    });

    document.getElementById('view-leaderboard').addEventListener('click', () => {
         window.location.href = 'leaderboard.html';
        console.log("Leaderboard clicked");
    });



    initPDFExport(); // Add PDF export button
}
