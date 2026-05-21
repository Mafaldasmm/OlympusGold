-- Olympus Gold Gym — demo seed data
-- Execute after schema.sql on an empty database

-- Password for all demo accounts: demo123
-- Hash generated with password_hash('demo123', PASSWORD_DEFAULT)

INSERT INTO users (username, password, email, name, role) VALUES
    ('ana.silva', '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'ana@olympusgold.pt', 'Ana Silva', 'trainer'),
    ('sofia.costa', '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'sofia@olympusgold.pt', 'Sofia Costa', 'trainer'),
    ('marco.rebelo', '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'marco@olympusgold.pt', 'Marco Rebelo', 'trainer'),
    ('ricardo.pereira', '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'ricardo@olympusgold.pt', 'Ricardo Pereira', 'trainer'),
    ('joao.membro', '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'joao@olympusgold.pt', 'João Santos', 'member'),
    ('admin', '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G', 'admin@olympusgold.pt', 'Administrador', 'admin');

INSERT INTO classes (title, description, type, trainer_id, day_of_week, start_time, end_time, capacity) VALUES
    (
        'Yoga Matinal',
        'Sessão de yoga suave para começar o dia com energia e foco.',
        'yoga',
        1,
        'monday',
        '08:00',
        '09:00',
        15
    ),
    (
        'Pilates Core',
        'Fortalecimento do core com exercícios de pilates no chão.',
        'pilates',
        2,
        'tuesday',
        '10:00',
        '11:00',
        12
    ),
    (
        'Zumba Energy',
        'Aula dinâmica de dança fitness com música latina.',
        'zumba',
        3,
        'wednesday',
        '18:30',
        '19:30',
        20
    ),
    (
        'HIIT Intenso',
        'Treino intervalado de alta intensidade para queima máxima.',
        'hiit',
        4,
        'friday',
        '07:00',
        '07:45',
        10
    );

INSERT INTO enrollments (user_id, class_id) VALUES
    (5, 1),
    (5, 3);

INSERT INTO reviews (user_id, class_id, rating, comment) VALUES
    (5, 1, 5, 'Excelente aula, ambiente calmo e treinadora muito atenta.'),
    (5, 3, 4, 'Muita energia! Recomendo para quem gosta de dançar.');
