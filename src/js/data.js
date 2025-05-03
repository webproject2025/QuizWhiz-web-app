const DATA_URL = '../src/data/quiz-data.json';

let allQuestions = [];

export async function fetchQuestions() {
    if (allQuestions.length > 0) return allQuestions;
    try {
        const response = await fetch(DATA_URL);
        allQuestions = await response.json();
        return allQuestions;
    } catch (error) {
        console.error("Could not fetch quiz data:", error);
        return [];
    }
}

export function getUniqueCategories(questions) {
    return [...new Set(questions.map(q => q.category))];
}

export function filterQuestions(questions, category, difficulty) {
    return questions.filter(q => {
        const categoryMatch = category === 'any' || q.category === category;
        const difficultyMatch = difficulty === 'any' || q.difficulty === difficulty;
        return categoryMatch && difficultyMatch;
    });
}