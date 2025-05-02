import { loadQuestions } from './data.js';
import { initUI } from './ui.js';

document.addEventListener('DOMContentLoaded', async () => {
  const questions = await loadQuestions('html');
  initUI(questions);
});