// Quiz state management
let currentPage = 1;
let quizzesPerPage = 6;
let currentTab = 'all-quizzes';
let currentFilters = {
    category: 'all',
    difficulty: 'all',
    sort: 'recent',
    search: ''
};

// DOM Elements
const quizGrid = document.querySelector('.quiz-grid');
const categoryFilter = document.getElementById('category-filter');
const difficultyFilter = document.getElementById('difficulty-filter');
const sortFilter = document.getElementById('sort-filter');
const searchInput = document.getElementById('quiz-search');
const tabButtons = document.querySelectorAll('.quiz-tab');
const tabContents = document.querySelectorAll('.tab-content');
const paginationContainer = document.querySelector('.pagination');

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    initializeEventListeners();
    loadQuizzes();
});

function initializeEventListeners() {
    // Filter event listeners
    categoryFilter.addEventListener('change', handleFilterChange);
    difficultyFilter.addEventListener('change', handleFilterChange);
    sortFilter.addEventListener('change', handleFilterChange);
    searchInput.addEventListener('input', debounce(handleFilterChange, 300));

    // Tab event listeners
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab');
            switchTab(tabId);
        });
    });
}

// Tab switching
function switchTab(tabId) {
    // Update active states
    tabButtons.forEach(btn => btn.classList.remove('active'));
    tabContents.forEach(content => content.classList.remove('active'));
    
    document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
    document.getElementById(tabId).classList.add('active');
    
    currentTab = tabId;
    currentPage = 1;
    loadQuizzes();
}

// Filter handling
function handleFilterChange() {
    currentFilters = {
        category: categoryFilter.value,
        difficulty: difficultyFilter.value,
        sort: sortFilter.value,
        search: searchInput.value.trim()
    };
    currentPage = 1;
    loadQuizzes();
}

// Quiz loading
async function loadQuizzes() {
    try {
        const response = await fetch('/QuizWhiz-web-app/api/get_quizzes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                page: currentPage,
                per_page: quizzesPerPage,
                tab: currentTab,
                filters: currentFilters
            })
        });

        if (!response.ok) {
            throw new Error('Failed to fetch quizzes');
        }

        const data = await response.json();
        renderQuizzes(data.quizzes);
        updatePagination(data.total_pages);
    } catch (error) {
        console.error('Error loading quizzes:', error);
        showError('Failed to load quizzes. Please try again later.');
    }
}

// Quiz rendering
function renderQuizzes(quizzes) {
    const quizGrid = document.querySelector(`#${currentTab} .quiz-grid`);
    if (!quizGrid) return;

    if (quizzes.length === 0) {
        quizGrid.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">📚</div>
                <h3 class="empty-title">No Quizzes Found</h3>
                <p class="empty-description">Try adjusting your filters or search criteria.</p>
            </div>
        `;
        return;
    }

    quizGrid.innerHTML = quizzes.map(quiz => createQuizCard(quiz)).join('');
}

// Quiz card creation
function createQuizCard(quiz) {
    const badgeClass = getBadgeClass(quiz.status);
    const badgeText = getBadgeText(quiz.status);
    const actions = getQuizActions(quiz);

    return `
        <div class="quiz-card">
            <div class="quiz-image">
                <img src="${quiz.image_url || '/placeholder.svg?height=160&width=300'}" alt="${quiz.title}">
                <div class="quiz-badge ${badgeClass}">${badgeText}</div>
            </div>
            <div class="quiz-content">
                <h3 class="quiz-title">${quiz.title}</h3>
                <div class="quiz-meta">
                    <span>${quiz.category}</span>
                    <span>${quiz.difficulty}</span>
                </div>
                <p class="quiz-description">${quiz.description}</p>
                <div class="quiz-stats">
                    <span>${quiz.total_questions} Questions</span>
                    ${getStatsText(quiz)}
                </div>
                <div class="quiz-actions">
                    ${actions}
                </div>
            </div>
        </div>
    `;
}

// Helper functions
function getBadgeClass(status) {
    switch (status) {
        case 'completed': return 'completed';
        case 'in_progress': return 'in-progress';
        case 'created': return 'created';
        default: return '';
    }
}

function getBadgeText(status) {
    switch (status) {
        case 'completed': return 'Completed';
        case 'in_progress': return 'In Progress';
        case 'created': return 'Created';
        default: return '';
    }
}

function getStatsText(quiz) {
    if (quiz.status === 'completed') {
        return `<span>Score: ${quiz.score}%</span>`;
    } else if (quiz.status === 'created') {
        return `<span>Attempts: ${quiz.attempts}</span>`;
    } else if (quiz.status === 'in_progress') {
        return `<span>Progress: ${quiz.progress}%</span>`;
    }
    return '';
}

function getQuizActions(quiz) {
    switch (quiz.status) {
        case 'completed':
            return `
                <a href="#retry" class="btn btn-outline" onclick="retryQuiz(${quiz.quiz_id})">Retry</a>
                <a href="#view" class="btn btn-primary" onclick="viewResults(${quiz.quiz_id})">View Results</a>
            `;
        case 'in_progress':
            return `
                <a href="#continue" class="btn btn-primary" onclick="continueQuiz(${quiz.quiz_id})">Continue</a>
            `;
        case 'created':
            return `
                <a href="#edit" class="btn btn-outline" onclick="editQuiz(${quiz.quiz_id})">Edit</a>
                <a href="#stats" class="btn btn-primary" onclick="viewStats(${quiz.quiz_id})">View Stats</a>
            `;
        default:
            return `
                <a href="quiz.html?id=${quiz.quiz_id}" class="btn btn-primary">Take Quiz</a>
            `;
    }
}

// Pagination
function updatePagination(totalPages) {
    if (!paginationContainer) return;

    let paginationHTML = `
        <button class="pagination-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">&laquo;</button>
    `;

    for (let i = 1; i <= totalPages; i++) {
        paginationHTML += `
            <button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>
        `;
    }

    paginationHTML += `
        <button class="pagination-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">&raquo;</button>
    `;

    paginationContainer.innerHTML = paginationHTML;
}

function changePage(page) {
    if (page < 1) return;
    currentPage = page;
    loadQuizzes();
}

// Quiz actions
function retryQuiz(quizId) {
    window.location.href = `quiz.html?id=${quizId}&retry=true`;
}

function viewResults(quizId) {
    window.location.href = `quiz-results.html?id=${quizId}`;
}

function continueQuiz(quizId) {
    window.location.href = `quiz.html?id=${quizId}&continue=true`;
}

function editQuiz(quizId) {
    window.location.href = `edit-quiz.html?id=${quizId}`;
}

function viewStats(quizId) {
    window.location.href = `quiz-stats.html?id=${quizId}`;
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showError(message) {
    // Implement error notification system
    console.error(message);
} 