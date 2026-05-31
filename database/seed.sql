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
    ('marta.ferreira',  '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'marta@olympusgold.pt',   'Marta Ferreira', 'member'),
    ('tiago.alves',     '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'tiago@olympusgold.pt',   'Tiago Alves',    'member'),
    ('ines.martins',    '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'ines@olympusgold.pt',    'Inês Martins',   'member'),
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

INSERT INTO trainer_certifications (trainer_id, name) VALUES
    (1, 'Instrutora de Yoga RYT-200'),
    (1, 'Primeiros Socorros Desportivos'),
    (2, 'Pilates Mat — Nível Avançado'),
    (3, 'Zumba Basic 1 License'),
    (3, 'Aeróbica em Grupo'),
    (4, 'Personal Trainer Certificado'),
    (4, 'Cycling Indoor Instructor');

INSERT INTO classes (title, description, type, trainer_id, day_of_week, start_time, end_time, capacity) VALUES
    ('Yoga Matinal',  'Sessão de yoga suave para começar o dia com energia e foco. Ideal para quem procura mobilidade e relaxamento antes do trabalho.', 'yoga',    1, 'monday',    '08:00', '09:00', 15),
    ('Vibe Cycle',    'Treino de ciclismo indoor em grupo, feito ao ritmo de música motivadora. Intervalos variados para melhorar resistência.', 'cycling', 4, 'monday',    '17:00', '18:00', 15),
    ('Pilates Core',  'Fortalecimento do core com exercícios de pilates no chão. Trabalho de estabilidade, respiração e controlo lombar.', 'pilates', 2, 'tuesday',   '10:00', '11:00', 12),
    ('The Ring',      'Treino estruturado de alta intensidade que combina condicionamento físico com técnicas de combate. Luvas e wraps recomendados.', 'boxing',  1, 'tuesday',   '17:00', '18:00', 15),
    ('Zumba Energy',  'Aula dinâmica de dança fitness com música latina. Ambiente descontraído, sem necessidade de experiência prévia.', 'zumba',   3, 'wednesday', '18:30', '19:30', 20),
    ('Aero',          'Aula que combina música, coordenação motora e exercícios cardiovasculares focados na queima de calorias.', 'hiit',    4, 'thursday',  '17:30', '18:30', 15),
    ('HIIT Intenso',  'Treino intervalado de alta intensidade para queima máxima. Blocos curtos com recuperação ativa — nível intermédio/avançado.', 'hiit',    4, 'friday',    '07:00', '07:45', 10),
    ('Zumba Party',   'Treino aeróbico de alta energia que transforma o exercício numa verdadeira festa de dança.', 'zumba',   3, 'friday',    '19:00', '20:00', 18);

INSERT INTO equipment (name, type, area, status) VALUES
    ('Banco de supino',        'strength', 'main', 'available'),
    ('Smith machine',          'strength', 'main', 'in_use'),
    ('Passadeira elétrica 1',  'cardio',   'main', 'in_use'),
    ('Passadeira elétrica 2',  'cardio',   'main', 'available'),
    ('Bicicleta estática A',   'cardio',   'main', 'available'),
    ('Bicicleta estática B',   'cardio',   'main', 'maintenance'),
    ('Leg press',              'strength', 'main', 'in_use'),
    ('Cabos crossover',        'functional','main', 'available'),
    ('Rack de agachamento',    'strength', 'main', 'available'),
    ('Elíptica',               'cardio',   'main', 'maintenance');

UPDATE classes SET featured = 1 WHERE title IN ('Yoga Matinal', 'Zumba Energy');
UPDATE users SET featured = 1 WHERE username IN ('ana.silva', 'ricardo.pereira');

-- Inscrições (joão=5, marta=6, tiago=7, inês=8 após inserts acima)
INSERT INTO enrollments (user_id, class_id) VALUES
    (5, 1),
    (5, 5),
    (6, 3),
    (6, 4),
    (7, 2),
    (7, 6),
    (8, 5),
    (8, 7),
    (8, 8);

-- HIIT Intenso (id 7) quase cheio — lista de espera
INSERT INTO enrollments (user_id, class_id) VALUES
    (6, 7);

INSERT INTO class_waitlist (user_id, class_id) VALUES
    (5, 7);

INSERT INTO reviews (user_id, class_id, rating, comment) VALUES
    (5, 1, 5, 'Excelente aula, ambiente calmo e treinadora muito atenta. Voltarei na próxima semana.'),
    (5, 5, 4, 'Muita energia! Recomendo para quem gosta de dançar. Só faltou mais espaço na sala.'),
    (6, 3, 5, 'Pilates muito bem explicado. Senti melhorias na postura ao fim de três semanas.'),
    (6, 4, 3, 'Treino exigente — bom para quem já tem base. Para iniciantes pode ser intenso demais.'),
    (7, 2, 4, 'Ciclismo indoor divertido, música fixe. Bicicletas confortáveis.'),
    (7, 6, 2, 'Gostei da ideia mas o ar condicionado estava fraco e a sala ficou muito quente.'),
    (8, 5, 5, 'A melhor Zumba em que já estive no Porto. Professor super motivador!'),
    (8, 8, 4, 'Festa garantida às sextas. Cheguei 10 minutos tarde e ainda assim entrei bem na aula.');
