import { fetchQuestions, getUniqueCategories, filterQuestions } from "./data.js"
import { Timer } from "./timer.js"
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
  displayChart,
} from "./ui.js"

let currentQuestions = []
let currentQuestionIndex = 0
let score = 0
let username = "Guest"
let selectedAnswers = []
let quizStartTime
let questionTimer = null
const STORAGE_KEYS = {
  CURRENT_USER: "quizwhiz_current_user",
  HIGH_SCORES: "quizwhiz_high_scores",
  USER_HISTORY: "quizwhiz_user_history",
}
function shuffleArray(array) {
  for (let i = array.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[array[i], array[j]] = [array[j], array[i]]
  }
}
function initializeLocalStorage() {
  if (!localStorage.getItem(STORAGE_KEYS.HIGH_SCORES)) {
    localStorage.setItem(STORAGE_KEYS.HIGH_SCORES, JSON.stringify({}))
  }

  if (!localStorage.getItem(STORAGE_KEYS.USER_HISTORY)) {
    localStorage.setItem(STORAGE_KEYS.USER_HISTORY, JSON.stringify({}))
  }
}
function handleUserLogin(username) {
  if (!username || username.trim() === "") {
    return { success: false, message: "Username cannot be empty" }
  }
  const cleanUsername = username.trim()
  localStorage.setItem(STORAGE_KEYS.CURRENT_USER, cleanUsername)
  const userHistory = JSON.parse(localStorage.getItem(STORAGE_KEYS.USER_HISTORY))
  if (!userHistory[cleanUsername]) {
    userHistory[cleanUsername] = {
      quizzesTaken: 0,
      totalScore: 0,
      lastLogin: new Date().toISOString(),
    }
    localStorage.setItem(STORAGE_KEYS.USER_HISTORY, JSON.stringify(userHistory))
  } else {
    // Update last login time
    userHistory[cleanUsername].lastLogin = new Date().toISOString()
    localStorage.setItem(STORAGE_KEYS.USER_HISTORY, JSON.stringify(userHistory))
  }

  return { success: true, message: "Login successful" }
}
function getCurrentUser() {
  return localStorage.getItem(STORAGE_KEYS.CURRENT_USER) || null
}
function updateHighScores(category, difficulty, score, totalQuestions) {
  const username = getCurrentUser()
  if (!username) 
    return false
  const scorePercentage = Math.round((score / totalQuestions) * 100)
  const highScores = JSON.parse(localStorage.getItem(STORAGE_KEYS.HIGH_SCORES))
  if (!highScores[category]) {
    highScores[category] = {}
  }

  if (!highScores[category][difficulty]) {
    highScores[category][difficulty] = []
  }

  const categoryScores = highScores[category][difficulty]
  const userScoreIndex = categoryScores.findIndex((entry) => entry.username === username)
  const newScoreEntry = {
    username,
    score: scorePercentage,
    date: new Date().toISOString(),
  }

  let isNewRecord = false
  if (userScoreIndex !== -1) {
    if (scorePercentage > categoryScores[userScoreIndex].score) {
      categoryScores[userScoreIndex] = newScoreEntry
      isNewRecord = true
    }
  }
   else {
    categoryScores.push(newScoreEntry)
    isNewRecord = true
  }

  categoryScores.sort((a, b) => b.score - a.score)
  highScores[category][difficulty] = categoryScores.slice(0, 3)
  const userHistory = JSON.parse(localStorage.getItem(STORAGE_KEYS.USER_HISTORY))
  userHistory[username].quizzesTaken += 1
  userHistory[username].totalScore += scorePercentage
  localStorage.setItem(STORAGE_KEYS.USER_HISTORY, JSON.stringify(userHistory))
  localStorage.setItem(STORAGE_KEYS.HIGH_SCORES, JSON.stringify(highScores))
  sessionStorage.setItem("isNewRecord", isNewRecord.toString())

  return isNewRecord
}
function startQuiz() {
  console.log("Starting quiz...")
  username = elements.usernameInput.value.trim() || "Guest"
  const loginResult = handleUserLogin(username)

  if (!loginResult.success) {
    alert(loginResult.message)
    return
  }
  elements.quizUsername.textContent = username

  const selectedCategory = elements.categorySelect.value
  const selectedDifficulty = elements.difficultySelect.value

  console.log(`Filters - Category: ${selectedCategory}, Difficulty: ${selectedDifficulty}`)

  fetchQuestions()
    .then((allQuestions) => {
      currentQuestions = filterQuestions(allQuestions, selectedCategory, selectedDifficulty)

      if (currentQuestions.length === 0) {
        alert("No questions found for this criteria. Please try different options.")
        return
      }

      shuffleArray(currentQuestions)
      console.log("Filtered & shuffled questions:", currentQuestions)

      currentQuestionIndex = 0
      score = 0
      selectedAnswers = []
      updateScoreDisplay(score)

      showScreen("quiz-screen")
      displayQuestion(currentQuestions[currentQuestionIndex], currentQuestionIndex, currentQuestions.length)
      quizStartTime = Date.now()

      questionTimer = new Timer(20, elements.timeLeft, handleTimeOut)
      questionTimer.start()
    })
    .catch((error) => {
      console.error("Failed to initialize quiz:", error)
      const errorDiv = document.createElement("div")
      errorDiv.className = "error-message"
      errorDiv.innerHTML = `
            <p>⚠️ Could not load quiz data</p>
            <button onclick="location.reload()">Try Again</button>
        `
      elements.startScreen.prepend(errorDiv)
    })
}

function handleAnswerSelection(event) {
  const selectedButton = event.target.closest(".option-btn")
  if (!selectedButton || selectedButton.disabled) 
    return
  questionTimer.stop()
  const selectedAnswer = selectedButton.dataset.answer
  const currentQuestion = currentQuestions[currentQuestionIndex]
  const correctAnswer = currentQuestion.correctAnswer
  const isCorrect = selectedAnswer === correctAnswer

  selectedAnswers.push({
    question: currentQuestion.question,
    options: currentQuestion.options,
    userAnswer: selectedAnswer,
    correctAnswer,
    explanation: currentQuestion.explanation,
  })

  if (isCorrect) {
    score++
    updateScoreDisplay(score)
  }
  showAnswerFeedback(correctAnswer, selectedButton)
  showExplanation(currentQuestion.explanation)
  if (currentQuestionIndex >= currentQuestions.length - 1) {
    elements.nextQuestionBtn.textContent = "Finish Quiz"
  }
}

function handleNextQuestion() {
  currentQuestionIndex++
  if (currentQuestionIndex < currentQuestions.length) {
    elements.nextQuestionBtn.textContent = "Next"
    displayQuestion(currentQuestions[currentQuestionIndex], currentQuestionIndex, currentQuestions.length)
    questionTimer.reset(15)
    questionTimer.start()
  } else {
    console.log("Quiz finished!")
    endQuiz()
  }
}

function endQuiz() {
  const timeTaken = Math.round((Date.now() - quizStartTime) / 1000)
  const totalQuestions = currentQuestions.length
  const accuracy = totalQuestions > 0 ? Math.round((score / totalQuestions) * 100) : 0
  const category = elements.categorySelect.value
  const difficulty = elements.difficultySelect.value
  const isNewRecord = updateHighScores(category, difficulty, score, totalQuestions)
  displayResults(username, score, totalQuestions, timeTaken, category)
  if (isNewRecord && elements.highscoreMessage) {
    elements.highscoreMessage.innerHTML = `
      <div class="new-record">
        <span class="trophy">🏆</span> New Personal Record! <span class="trophy">🏆</span>
      </div>
    `
    elements.highscoreMessage.classList.add("show")
  } else if (elements.highscoreMessage) {
    elements.highscoreMessage.textContent = ""
    elements.highscoreMessage.classList.remove("show")
  }

  updateLeaderboardDisplay(category, difficulty)
  displayChart(score, totalQuestions - score)

  localStorage.setItem(
    "lastQuizResults",
    JSON.stringify({
      username,
      score,
      accuracy,
      timeTaken,
      totalQuestions,
      category,
      difficulty,
      answeredQuestions: selectedAnswers,
      isNewRecord,
    }),
  )

  showScreen("results-screen")
}
function updateLeaderboardDisplay(category, difficulty) {
  const leaderboardList = document.getElementById("leaderboard-list")
  const leaderboardCategory = document.getElementById("leaderboard-category")
  if (!leaderboardList || !leaderboardCategory) return
  leaderboardCategory.textContent = `${category} - ${difficulty}`
  const highScores = JSON.parse(localStorage.getItem(STORAGE_KEYS.HIGH_SCORES))
  if (!highScores[category] || !highScores[category][difficulty] || highScores[category][difficulty].length === 0) {
    leaderboardList.innerHTML = `<li class="no-scores">No high scores yet</li>`
    return
  }
  const topScores = highScores[category][difficulty]
  const currentUser = getCurrentUser()
  leaderboardList.innerHTML = topScores
    .map((entry, index) => {
      const isCurrentUser = entry.username === currentUser
      const date = new Date(entry.date).toLocaleDateString()

      return `
      <li class="leaderboard-entry ${isCurrentUser ? "current-user" : ""}">
        <span class="leaderboard-name">${entry.username}</span>
        <span class="leaderboard-score">${entry.score}%</span>
        <span class="leaderboard-date">${date}</span>
      </li>
    `
    })
    .join("")
}

function handleTimeOut() {
  selectedAnswers.push({
    question: currentQuestions[currentQuestionIndex].question,
    options: currentQuestions[currentQuestionIndex].options,
    userAnswer: null,
    correctAnswer: currentQuestions[currentQuestionIndex].correctAnswer,
    explanation: currentQuestions[currentQuestionIndex].explanation,
  })

  showAnswerFeedback(currentQuestions[currentQuestionIndex].correctAnswer, null)
  showExplanation(currentQuestions[currentQuestionIndex].explanation)
  setTimeout(handleNextQuestion, 1500)
}

function setupEventListeners() {
  elements.startQuizBtn.addEventListener("click", startQuiz)
  elements.optionsContainer.addEventListener("click", handleAnswerSelection)
  elements.nextQuestionBtn.addEventListener("click", handleNextQuestion)

  elements.themeToggleBtn.addEventListener("click", () => {
    const currentTheme = document.body.classList.contains("dark-mode") ? "light-mode" : "dark-mode"
    applyTheme(currentTheme)
  })

  elements.playAgainBtn.addEventListener("click", () => {
    elements.categorySelect.value = "any"
    elements.difficultySelect.value = "any"
    showScreen("start-screen")
  })

  elements.reviewAnswersBtn.addEventListener("click", () => {
    window.location.href = "review.html"
  })
  const savedUsername = getCurrentUser()
  if (savedUsername) {
    elements.usernameInput.value = savedUsername
  }
}
async function initializeApp() {
  console.log("Initializing QuizWhiz+...")
  initializeLocalStorage()
  loadTheme()
  setupEventListeners()
  showScreen("start-screen")
  try {
    const questions = await fetchQuestions()
    const categories = getUniqueCategories(questions)
    populateCategoryDropdown(categories)
    console.log("App initialized successfully.")
  } catch (error) {
    console.error("Initialization failed:", error)
    const errorDiv = document.createElement("div")
    errorDiv.className = "error-message"
    errorDiv.innerHTML = `
            <p>Could not load quiz data</p>
            <button onclick="location.reload()">Please, Try Again</button>
        `
    elements.startScreen.prepend(errorDiv)
  }
}
initializeApp()
