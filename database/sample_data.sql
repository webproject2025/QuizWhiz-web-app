-- Insert sample users
INSERT INTO users (email, password_hash) VALUES
('john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password: password
('jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

-- Insert sample quizzes
INSERT INTO quizzes (title, description, category, difficulty, created_by, total_questions, time_limit) VALUES
('Quantum Physics Basics', 'Test your knowledge of fundamental quantum physics concepts', 'Science', 'medium', 1, 10, 15),
('Web Development Fundamentals', 'Basic concepts of HTML, CSS, and JavaScript', 'Programming', 'easy', 1, 10, 20),
('World History: Ancient Civilizations', 'Test your knowledge of ancient civilizations', 'History', 'medium', 2, 10, 15);

-- Insert questions for Quantum Physics Basics
INSERT INTO questions (quiz_id, question_text, question_type, points, question_order) VALUES
(1, 'What is the principle that states it is impossible to know both the position and momentum of a particle with perfect precision?', 'multiple_choice', 1, 1),
(1, 'Which equation describes the wave function of a quantum system?', 'multiple_choice', 1, 2),
(1, 'What is the name of the thought experiment that demonstrates quantum superposition?', 'multiple_choice', 1, 3),
(1, 'Which particle is the quantum of light?', 'multiple_choice', 1, 4),
(1, 'What is the term for the phenomenon where two particles become correlated?', 'multiple_choice', 1, 5);

-- Insert answers for Quantum Physics questions
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(1, 'Heisenberg Uncertainty Principle', true),
(1, 'Pauli Exclusion Principle', false),
(1, 'Schrödinger Equation', false),
(1, 'Planck Constant', false),

(2, 'Schrödinger Equation', true),
(2, 'Einstein Field Equations', false),
(2, 'Maxwell Equations', false),
(2, 'Dirac Equation', false),

(3, 'Schrödinger\'s Cat', true),
(3, 'Einstein\'s Photoelectric Effect', false),
(3, 'Bohr\'s Atomic Model', false),
(3, 'Planck\'s Black Body Radiation', false),

(4, 'Photon', true),
(4, 'Electron', false),
(4, 'Neutron', false),
(4, 'Proton', false),

(5, 'Quantum Entanglement', true),
(5, 'Quantum Tunneling', false),
(5, 'Quantum Superposition', false),
(5, 'Quantum Decoherence', false);

-- Insert questions for Web Development Fundamentals
INSERT INTO questions (quiz_id, question_text, question_type, points, question_order) VALUES
(2, 'What does HTML stand for?', 'multiple_choice', 1, 1),
(2, 'Which CSS property is used to change the text color?', 'multiple_choice', 1, 2),
(2, 'What is the correct way to declare a JavaScript variable?', 'multiple_choice', 1, 3),
(2, 'Which HTML tag is used to create a hyperlink?', 'multiple_choice', 1, 4),
(2, 'What is the purpose of CSS?', 'multiple_choice', 1, 5);

-- Insert answers for Web Development questions
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(6, 'Hyper Text Markup Language', true),
(6, 'High Tech Modern Language', false),
(6, 'Hyper Transfer Markup Language', false),
(6, 'Hyper Text Modern Language', false),

(7, 'color', true),
(7, 'text-color', false),
(7, 'font-color', false),
(7, 'text-style', false),

(8, 'let x = 5;', true),
(8, 'variable x = 5;', false),
(8, 'v x = 5;', false),
(8, 'x = 5;', false),

(9, '<a>', true),
(9, '<link>', false),
(9, '<href>', false),
(9, '<url>', false),

(10, 'To style and layout web pages', true),
(10, 'To create web page structure', false),
(10, 'To add interactivity to web pages', false),
(10, 'To store data', false); 