-- Olympus Gold Gym — demo seed data
-- Execute after schema.sql on an empty database

-- Password for all demo accounts: demo123
-- Hash generated with password_hash('demo123', PASSWORD_DEFAULT)

INSERT INTO users (username, password, email, name, role) VALUES
    ('ana.silva',       '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'ana@olympusgold.pt',     'Ana Silva',      'trainer'),
    ('sofia.costa',     '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'sofia@olympusgold.pt',   'Sofia Costa',    'trainer'),
    ('marco.rebelo',    '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'marco@olympusgold.pt',   'Marco Rebelo',   'trainer'),
    ('ricardo.pereira', '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'ricardo@olympusgold.pt', 'Ricardo Pereira','trainer'),
    ('joao.membro',     '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'joao@olympusgold.pt',    'João Santos',    'member'),
    ('admin',           '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'admin@olympusgold.pt',   'Administrador',  'admin');

UPDATE users SET bio = 'Instrutora de yoga e bem-estar com mais de 8 anos de experiência. Foca em mobilidade, respiração consciente e aulas acessíveis a todos os níveis.'
WHERE username = 'ana.silva';

UPDATE users SET bio = 'Especialista em pilates no solo e fortalecimento do core. Ajuda os alunos a melhorar postura, estabilidade e controlo corporal.'
WHERE username = 'sofia.costa';

UPDATE users SET bio = 'Treinador de dança fitness e aulas em grupo de alta energia. Cria sessões motivadoras com foco em coordenação e resistência cardiovascular.'
WHERE username = 'marco.rebelo';

UPDATE users SET bio = 'Personal trainer orientado para HIIT, ciclismo indoor e condicionamento. Prioriza técnica segura e progressão adaptada a cada aluno.'
WHERE username = 'ricardo.pereira';

INSERT INTO trainer_specializations (trainer_id, name) VALUES
    (1, 'Yoga'),
    (1, 'Mobilidade'),
    (1, 'Boxe fitness'),
    (2, 'Pilates'),
    (2, 'Core training'),
    (2, 'Postura'),
    (3, 'Zumba'),
    (3, 'Dança fitness'),
    (3, 'Aulas em grupo'),
    (4, 'HIIT'),
    (4, 'Ciclismo indoor'),
    (4, 'Condicionamento físico');

INSERT INTO classes (title, description, type, trainer_id, day_of_week, start_time, end_time, capacity) VALUES
    ('Yoga Matinal',  'Sessão de yoga suave para começar o dia com energia e foco.',                                                          'yoga',    1, 'monday',    '08:00', '09:00', 15),
    ('Vibe Cycle',    'Treino de ciclismo indoor em grupo, feito ao ritmo de música motivadora.',                                             'cycling', 4, 'monday',    '17:00', '18:00', 15),
    ('Pilates Core',  'Fortalecimento do core com exercícios de pilates no chão.',                                                            'pilates', 2, 'tuesday',   '10:00', '11:00', 12),
    ('The Ring',      'Treino estruturado de alta intensidade que combina condicionamento físico com técnicas de combate.',                    'boxing',  1, 'tuesday',   '17:00', '18:00', 15),
    ('Zumba Energy',  'Aula dinâmica de dança fitness com música latina.',                                                                    'zumba',   3, 'wednesday', '18:30', '19:30', 20),
    ('Aero',          'Aula que combina música, coordenação motora e exercícios cardiovasculares focados na queima de calorias.',              'hiit',    4, 'thursday',  '17:30', '18:30', 15),
    ('HIIT Intenso',  'Treino intervalado de alta intensidade para queima máxima.',                                                           'hiit',    4, 'friday',    '07:00', '07:45', 10),
    ('Zumba Party',   'Treino aeróbico de alta energia que transforma o exercício numa verdadeira festa de dança.',                           'zumba',   3, 'friday',    '19:00', '20:00', 18);

INSERT INTO enrollments (user_id, class_id) VALUES
    (5, 1),
    (5, 5);

INSERT INTO reviews (user_id, class_id, rating, comment) VALUES
    (5, 1, 5, 'Excelente aula, ambiente calmo e treinadora muito atenta.'),
    (5, 5, 4, 'Muita energia! Recomendo para quem gosta de dançar.');
