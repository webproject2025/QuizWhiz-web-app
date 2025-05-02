// Data loading and randomization
export const loadQuestions = async (category) => {
    try {
      const response = await fetch('../data/quiz-data.json');
      const data = await response.json();
      return shuffleArray(data[category]).slice(0, 10); // 10 random questions
    } catch (error) {
      console.error('Failed to load questions:', error);
      return [];
    }
  };
  
  // Fisher-Yates shuffle algorithm
  const shuffleArray = (array) => {
    for (let i = array.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
  };