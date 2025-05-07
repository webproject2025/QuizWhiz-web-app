import { elements, loadTheme } from "./ui.js"

const STORAGE_KEYS = {
  CURRENT_USER: "quizwhiz_current_user",
  HIGH_SCORES: "quizwhiz_high_scores",
  USER_HISTORY: "quizwhiz_user_history",
}
export function loadReviewData() {
  const results = JSON.parse(localStorage.getItem("lastQuizResults"))
  if (!results) {
    elements.reviewList.innerHTML = "<p>No review data available.</p>"
    return
  }
  const reviewHeader = document.createElement("div")
  reviewHeader.className = "review-header"
  reviewHeader.innerHTML = `
    <div class="review-summary">
      <h2>Quiz Results for ${results.username}</h2>
      <div class="summary-stats">
        <div class="stat-item">
          <span class="stat-label">Score:</span>
          <span class="stat-value">${results.score}/${results.totalQuestions} (${results.accuracy}%)</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">Category:</span>
          <span class="stat-value">${results.category}</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">Difficulty:</span>
          <span class="stat-value">${results.difficulty}</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">Time Taken:</span>
          <span class="stat-value">${results.timeTaken} seconds</span>
        </div>
      </div>
    </div>
  `
  if (results.isNewRecord) {
    const newRecordBanner = document.createElement("div")
    newRecordBanner.className = "new-record-banner"
    newRecordBanner.innerHTML = `
      <h3> New Personal Record! </h3>
      <p>Congratulations! You haveve set a new personal record.</p>
    `
    reviewHeader.appendChild(newRecordBanner)
  }
  elements.reviewList.parentNode.insertBefore(reviewHeader, elements.reviewList)
  elements.reviewList.innerHTML = results.answeredQuestions
    .map(
      (q, index) => `
        <div class="review-item fade-in">
            <h3>Question ${index + 1}: ${q.question}</h3>
            <div class="options-review">
                ${q.options
                  .map(
                    (opt) => `
                    <div class="option 
                        ${opt === q.correctAnswer ? "correct" : ""}
                        ${opt === q.userAnswer && opt !== q.correctAnswer ? "incorrect" : ""}">
                        ${opt}
                        ${opt === q.userAnswer ? '<span class="user-choice">(Your Choice)</span>' : ""}
                    </div>
                `,
                  )
                  .join("")}
            </div>
            <p class="explanation">${q.explanation}</p>
        </div>
    `,
    )
    .join("")
  addLeaderboardSection(results.category, results.difficulty)
  addUserStatsSection()
}
function addLeaderboardSection(category, difficulty) {
  let leaderboardSection = document.getElementById("leaderboard-section")
  if (!leaderboardSection) {
    leaderboardSection = document.createElement("div")
    leaderboardSection.id = "leaderboard-section"
    leaderboardSection.className = "review-section"
    const returnHomeBtn = document.getElementById("return-home")
    returnHomeBtn.parentNode.insertBefore(leaderboardSection, returnHomeBtn)
  }
  const highScores = JSON.parse(localStorage.getItem(STORAGE_KEYS.HIGH_SCORES))
  if (!highScores[category] || !highScores[category][difficulty] || highScores[category][difficulty].length === 0) {
    leaderboardSection.innerHTML = `
      <h2>Top Scores - ${category} (${difficulty})</h2>
      <div class="no-scores">
        <p>No high scores yet for this category and difficulty.</p>
        <p>Be the first to set a record!</p>
      </div>
    `
    return
  }
  const topScores = highScores[category][difficulty]
  const currentUser = localStorage.getItem(STORAGE_KEYS.CURRENT_USER)
  let leaderboardHTML = `
    <h2>Top Scores - ${category} (${difficulty})</h2>
    <div class="leaderboard">
  `
  topScores.forEach((score, index) => {
    const isCurrentUser = score.username === currentUser
    const date = new Date(score.date).toLocaleDateString()
    leaderboardHTML += `
      <div class="leaderboard-entry ${isCurrentUser ? "current-user" : ""}">
        <div class="rank">#${index + 1}</div>
        <div class="username">${score.username}</div>
        <div class="score">${score.score}%</div>
        <div class="date">${date}</div>
      </div>
    `
  })
  leaderboardHTML += `</div>`
  leaderboardSection.innerHTML = leaderboardHTML
}
function addUserStatsSection() {
  let userStatsSection = document.getElementById("user-stats-section")
  if (!userStatsSection) {
    userStatsSection = document.createElement("div")
    userStatsSection.id = "user-stats-section"
    userStatsSection.className = "review-section"
    const returnHomeBtn = document.getElementById("return-home")
    returnHomeBtn.parentNode.insertBefore(userStatsSection, returnHomeBtn)
  }
  const currentUser = localStorage.getItem(STORAGE_KEYS.CURRENT_USER)
  if (!currentUser) {
    userStatsSection.style.display = "none"
    return
  }
  const userHistory = JSON.parse(localStorage.getItem(STORAGE_KEYS.USER_HISTORY))
  if (!userHistory[currentUser]) {
    userStatsSection.style.display = "none"
    return
  }
  const userStats = userHistory[currentUser]
  const averageScore = userStats.quizzesTaken > 0 ? Math.round(userStats.totalScore / userStats.quizzesTaken) : 0
  userStatsSection.innerHTML = `
    <h2>Your Stats</h2>
    <div class="user-stats">
      <div class="stat-item">
        <span class="stat-label">Quizzes Taken:</span>
        <span class="stat-value">${userStats.quizzesTaken}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Average Score:</span>
        <span class="stat-value">${averageScore}%</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Last Quiz:</span>
        <span class="stat-value">${new Date(userStats.lastLogin).toLocaleString()}</span>
      </div>
    </div>
  `
}
function setupEventListeners() {
  document.getElementById("return-home").addEventListener("click", () => {
    window.location.href = "index.html"
  })

  document.getElementById("theme-toggle-btn").addEventListener("click", () => {
    const currentTheme = document.body.classList.contains("dark-mode") ? "light-mode" : "dark-mode"
    document.body.className = currentTheme
    localStorage.setItem("quizTheme", currentTheme)
  })
}
function initReviewPage() {
  loadTheme()
  setupEventListeners()
  loadReviewData()
}
initReviewPage()
