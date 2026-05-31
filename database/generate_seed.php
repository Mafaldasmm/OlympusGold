<?php

declare(strict_types=1);

/**
 * Generates database/seed.sql — run: php database/generate_seed.php > database/seed.sql
 */

$demoHash = '$2y$10$XYZAki5zRfK3p1JMxH5J.OFdfY7.W.QTVTdso2iFzbSmDH5OlGm.G';
$seedHash = '$2y$10$OfiUZcYq1hikDT23Q1Jymup.rXc4MDKpFME8uiRnl2w6/evSWbYoq';

$trainers = [
    ['ana.silva', 'ana@olympusgold.pt', 'Ana Silva', true],
    ['sofia.costa', 'sofia.costa@olympusgold.pt', 'Sofia Costa', false],
    ['marco.rebelo', 'marco.rebelo@olympusgold.pt', 'Marco Rebelo', false],
    ['ricardo.pereira', 'ricardo.pereira@olympusgold.pt', 'Ricardo Pereira', false],
    ['catarina.mendes', 'catarina.mendes@olympusgold.pt', 'Catarina Mendes', false],
    ['hugo.ferreira', 'hugo.ferreira@olympusgold.pt', 'Hugo Ferreira', false],
    ['beatriz.nogueira', 'beatriz.nogueira@olympusgold.pt', 'Beatriz Nogueira', false],
    ['diogo.carvalho', 'diogo.carvalho@olympusgold.pt', 'Diogo Carvalho', false],
    ['lucia.ramos', 'lucia.ramos@olympusgold.pt', 'Lúcia Ramos', false],
    ['andre.mota', 'andre.mota@olympusgold.pt', 'André Mota', false],
];

$members = [
    ['joao.membro', 'joao@olympusgold.pt', 'João Santos', true],
    ['marta.ferreira', 'marta.ferreira@gmail.com', 'Marta Ferreira', false],
    ['tiago.alves', 'tiago.alves@outlook.pt', 'Tiago Alves', false],
    ['ines.martins', 'ines.martins@sapo.pt', 'Inês Martins', false],
    ['carlos.ribeiro', 'carlos.ribeiro@icloud.com', 'Carlos Ribeiro', false],
    ['rita.nunes', 'rita.nunes@yahoo.pt', 'Rita Nunes', false],
    ['pedro.lima', 'pedro.lima@hotmail.com', 'Pedro Lima', false],
    ['sara.costa', 'sara.costa@gmail.com', 'Sara Costa', false],
    ['miguel.sousa', 'miguel.sousa@outlook.pt', 'Miguel Sousa', false],
    ['helena.pinto', 'helena.pinto@sapo.pt', 'Helena Pinto', false],
    ['bruno.gomes', 'bruno.gomes@gmail.com', 'Bruno Gomes', false],
    ['claudia.veiga', 'claudia.veiga@hotmail.com', 'Cláudia Veiga', false],
    ['nuno.teixeira', 'nuno.teixeira@outlook.pt', 'Nuno Teixeira', false],
    ['patricia.lopes', 'patricia.lopes@gmail.com', 'Patrícia Lopes', false],
    ['rafael.monteiro', 'rafael.monteiro@sapo.pt', 'Rafael Monteiro', false],
    ['vanessa.duarte', 'vanessa.duarte@hotmail.com', 'Vanessa Duarte', false],
    ['goncalo.henriques', 'goncalo.henriques@gmail.com', 'Gonçalo Henriques', false],
    ['joana.barbosa', 'joana.barbosa@outlook.pt', 'Joana Barbosa', false],
    ['filipe.correia', 'filipe.correia@sapo.pt', 'Filipe Correia', false],
    ['alice.freitas', 'alice.freitas@gmail.com', 'Alice Freitas', false],
    ['david.cunha', 'david.cunha@hotmail.com', 'David Cunha', false],
    ['mariana.silva', 'mariana.silva@outlook.pt', 'Mariana Silva', false],
    ['tiago.rocha', 'tiago.rocha@sapo.pt', 'Tiago Rocha', false],
    ['carolina.antunes', 'carolina.antunes@gmail.com', 'Carolina Antunes', false],
    ['paulo.neves', 'paulo.neves@hotmail.com', 'Paulo Neves', false],
    ['sofia.cardoso', 'sofia.cardoso@outlook.pt', 'Sofia Cardoso', false],
    ['luis.peixoto', 'luis.peixoto@sapo.pt', 'Luís Peixoto', false],
    ['teresa.azevedo', 'teresa.azevedo@gmail.com', 'Teresa Azevedo', false],
    ['ricardo.tavares', 'ricardo.tavares@hotmail.com', 'Ricardo Tavares', false],
    ['cristina.machado', 'cristina.machado@outlook.pt', 'Cristina Machado', false],
    ['hugo.baptista', 'hugo.baptista@sapo.pt', 'Hugo Baptista', false],
    ['ana.pereira', 'ana.pereira@gmail.com', 'Ana Pereira', false],
    ['jorge.fonseca', 'jorge.fonseca@hotmail.com', 'Jorge Fonseca', false],
    ['leonor.miranda', 'leonor.miranda@outlook.pt', 'Leonor Miranda', false],
    ['fabio.coelho', 'fabio.coelho@sapo.pt', 'Fábio Coelho', false],
    ['raquel.pinheiro', 'raquel.pinheiro@gmail.com', 'Raquel Pinheiro', false],
    ['manuel.figueiredo', 'manuel.figueiredo@hotmail.com', 'Manuel Figueiredo', false],
    ['diana.lourenco', 'diana.lourenco@outlook.pt', 'Diana Lourenço', false],
    ['vasco.trindade', 'vasco.trindade@sapo.pt', 'Vasco Trindade', false],
    ['catia.rego', 'catia.rego@gmail.com', 'Cátia Rego', false],
];

$admins = [
    ['admin', 'admin@olympusgold.pt', 'Administrador Demo', true],
    ['susana.gestao', 'susana.gestao@olympusgold.pt', 'Susana Gestão', false],
    ['rui.operacoes', 'rui.operacoes@olympusgold.pt', 'Rui Operações', false],
];

$trainerBios = [
    'Instrutora de yoga e bem-estar com mais de 8 anos de experiência. Foca em mobilidade, respiração consciente e aulas acessíveis a todos os níveis.',
    'Especialista em pilates no solo e fortalecimento do core. Ajuda os alunos a melhorar postura, estabilidade e controlo corporal.',
    'Treinador de dança fitness e aulas em grupo de alta energia. Cria sessões motivadoras com foco em coordenação e resistência cardiovascular.',
    'Personal trainer orientado para HIIT, ciclismo indoor e condicionamento. Prioriza técnica segura e progressão adaptada a cada aluno.',
    'Instrutora de yoga vinyasa e alongamentos ativos. Combina força leve com flexibilidade para todos os níveis.',
    'Especialista em musculação funcional e treino de força. Acompanha objetivos de hipertrofia com planos personalizados.',
    'Coach de pilates reformer e mobilidade. Trabalha recuperação pós-lesão e prevenção em pequenos grupos.',
    'Treinador de boxe fitness e condicionamento. Sessões técnicas com foco em segurança e progressão gradual.',
    'Instrutora de zumba e ritmos latinos. Cria ambiente positivo para iniciantes e alunos regulares.',
    'Especialista em cycling indoor e treinos intervalados. Música e cadência como motor da sessão.',
];

$classes = [
    ['Yoga Matinal', 'Sessão de yoga suave para começar o dia com energia e foco.', 'yoga', 1, 'monday', '08:00', '09:00', 15],
    ['Vibe Cycle', 'Ciclismo indoor em grupo com intervalos progressivos e música motivadora.', 'cycling', 4, 'monday', '17:00', '18:00', 15],
    ['Pilates Core', 'Fortalecimento do core com pilates no solo e controlo da respiração.', 'pilates', 2, 'tuesday', '10:00', '11:00', 12],
    ['The Ring', 'Condicionamento físico com técnicas de combate em circuito.', 'boxing', 8, 'tuesday', '17:00', '18:00', 15],
    ['Zumba Energy', 'Dança fitness latina para todos os níveis, ambiente descontraído.', 'zumba', 9, 'wednesday', '18:30', '19:30', 20],
    ['Aero HIIT', 'Cardio e coordenação com blocos de alta intensidade.', 'hiit', 4, 'thursday', '17:30', '18:30', 15],
    ['HIIT Sunrise', 'Treino matinal intervalado para começar o dia com energia.', 'hiit', 10, 'friday', '07:00', '07:45', 10],
    ['Zumba Party', 'Sessão festiva de dança fitness às sextas.', 'zumba', 3, 'friday', '19:00', '20:00', 18],
    ['Power Pilates', 'Pilates dinâmico focado em força e estabilidade.', 'pilates', 7, 'wednesday', '09:30', '10:30', 12],
    ['Sunset Yoga', 'Yoga ao fim do dia com ênfase em alongamentos profundos.', 'yoga', 5, 'thursday', '19:00', '20:00', 18],
    ['Boxe Técnico', 'Técnica de golpes, footwork e condicionamento metabólico.', 'boxing', 8, 'saturday', '10:00', '11:00', 14],
    ['Cycle Endurance', 'Resistência em bicicleta com blocos longos e recuperação ativa.', 'cycling', 10, 'saturday', '11:30', '12:30', 14],
    ['Mobilidade & Stretch', 'Sessão de mobilidade articular e flexibilidade guiada.', 'yoga', 1, 'wednesday', '12:00', '13:00', 16],
    ['Pump Strength', 'Treino de força em circuito com pesos livres e máquinas.', 'hiit', 6, 'monday', '19:30', '20:30', 12],
    ['Pilates Flow', 'Sequências fluídas de pilates para corpo inteiro.', 'pilates', 2, 'friday', '11:00', '12:00', 14],
    ['Dance Mix', 'Combinação de estilos latinos e pop para queima calórica.', 'zumba', 9, 'sunday', '18:00', '19:00', 20],
];

$reviewComments = [
    5 => ['Excelente aula, ambiente calmo e treinador muito atento.', 'Voltarei sem dúvida — saí com energia para o dia.', 'Muito bem estruturada, recomendo a quem trabalha sentado.', 'A melhor sessão de yoga que frequento no Porto.', 'Espaço agradável e explicações claras em cada postura.', 'Profissional, paciente e exigente na medida certa.', 'Aula consistente semana após semana.', 'Gostei da progressão ao longo do mês.'],
    4 => ['Muito boa experiência, só faltou um pouco mais de aquecimento.', 'Recomendo — grupo motivador e boa música.', 'Boa intensidade, saí a suar mas sem exageros.', 'Treino sólido, voltarei na próxima semana.', 'Quase perfeito; às vezes a sala fica cheia.', 'Boa relação esforço/resultado.', 'Instrutor competente e simpático.', 'Gostei, pena o horário ser tão cedo no inverno.'],
    3 => ['Boa aula, mas para iniciantes pode ser exigente.', 'Gostei da ideia, ar condicionado poderia melhorar.', 'Treino válido, esperava mais variedade nos exercícios.', 'Razoável — faltou organização na entrada da sala.', 'Interessante, vou dar mais uma oportunidade.', 'Nível médio-alto, vem preparado.', 'Aceitável, música um pouco alta para mim.', 'Bom treino, equipamento precisa de manutenção.'],
    2 => ['Demasiado cheio, difícil seguir o ritmo.', 'Sala quente e pouco ventilada na hora de ponta.', 'Esperava mais foco na técnica individual.', 'Horário bom, mas senti falta de orientação nos primeiros minutos.', 'Não foi para mim — intensidade excessiva.', 'Precisa melhorar a gestão da lista de espera.'],
    1 => ['Infelizmente não correspondeu às expectativas.', 'Muito desorganizado na sessão que frequentei.'],
];

function sqlStr(string $s): string
{
    return str_replace("'", "''", $s);
}

/**
 * Distribuição realista (maioria 4–5★, alguns 3★, raros 1–2★) com média global ≈ 4,5.
 *
 * @return list<int> ratings 1–5, length $count
 */
function buildNaturalReviewRatings(int $count, float $target = 4.5, float $tolerance = 0.05): array
{
    if ($count === 0) {
        return [];
    }

    // Percentagens alvo: ~66% cinco, ~22% quatro, ~9% três, ~2% dois, ~1% um → média ≈ 4,50
    $pctByStar = [5 => 66, 4 => 22, 3 => 9, 2 => 2, 1 => 1];
    $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $assigned = 0;
    foreach ($pctByStar as $star => $pct) {
        $n = (int) floor($count * $pct / 100);
        $counts[$star] = $n;
        $assigned += $n;
    }
    for ($star = 5; $star >= 1 && $assigned < $count; $star--) {
        $counts[$star]++;
        $assigned++;
    }

    $pool = [];
    for ($star = 5; $star >= 1; $star--) {
        for ($c = 0; $c < $counts[$star]; $c++) {
            $pool[] = $star;
        }
    }

    $len = count($pool);
    for ($pass = 0; $pass < $len * 3; $pass++) {
        $avg = array_sum($pool) / $len;
        if (abs($avg - $target) <= $tolerance) {
            break;
        }
        if ($avg < $target) {
            $idx = null;
            for ($i = 0; $i < $len; $i++) {
                if ($pool[$i] < 5) {
                    $idx = $i;
                    if ($pool[$i] <= 3) {
                        break;
                    }
                }
            }
            if ($idx === null) {
                break;
            }
            $pool[$idx]++;
        } else {
            $idx = null;
            for ($i = $len - 1; $i >= 0; $i--) {
                if ($pool[$i] > 1) {
                    $idx = $i;
                    if ($pool[$i] >= 5) {
                        break;
                    }
                }
            }
            if ($idx === null) {
                break;
            }
            $pool[$idx]--;
        }
    }

    for ($i = $len - 1; $i > 0; $i--) {
        $j = ($i * 17 + $len * 3 + 11) % ($i + 1);
        [$pool[$i], $pool[$j]] = [$pool[$j], $pool[$i]];
    }

    return $pool;
}

$out = [];
$out[] = '-- Olympus Gold Gym — seed data (generated)';
$out[] = '-- Demo accounts (password: demo123): admin, ana.silva, joao.membro';
$out[] = '-- Other seed users password: OgGym2026!';
$out[] = '';

$out[] = 'INSERT INTO users (username, password, email, name, role) VALUES';
$userRows = [];
foreach ($trainers as $t) {
    $hash = $t[3] ? $demoHash : $seedHash;
    $userRows[] = sprintf("    ('%s', '%s', '%s', '%s', 'trainer')", sqlStr($t[0]), $hash, sqlStr($t[1]), sqlStr($t[2]));
}
foreach ($members as $m) {
    $hash = $m[3] ? $demoHash : $seedHash;
    $userRows[] = sprintf("    ('%s', '%s', '%s', '%s', 'member')", sqlStr($m[0]), $hash, sqlStr($m[1]), sqlStr($m[2]));
}
foreach ($admins as $a) {
    $hash = $a[3] ? $demoHash : $seedHash;
    $userRows[] = sprintf("    ('%s', '%s', '%s', '%s', 'admin')", sqlStr($a[0]), $hash, sqlStr($a[1]), sqlStr($a[2]));
}
$out[] = implode(",\n", $userRows) . ';';
$out[] = '';

$trainerCount = count($trainers);
foreach ($trainers as $i => $t) {
    $id = $i + 1;
    $out[] = sprintf("UPDATE users SET bio = '%s' WHERE username = '%s';", sqlStr($trainerBios[$i]), sqlStr($t[0]));
}
$out[] = '';

$specs = [
    [1, ['Yoga', 'Mobilidade', 'Alongamentos']],
    [2, ['Pilates', 'Core training', 'Postura']],
    [3, ['Zumba', 'Dança fitness', 'Aulas em grupo']],
    [4, ['HIIT', 'Ciclismo indoor', 'Condicionamento']],
    [5, ['Yoga Vinyasa', 'Flexibilidade', 'Mindfulness']],
    [6, ['Musculação', 'Força', 'Treino funcional']],
    [7, ['Pilates', 'Mobilidade', 'Reabilitação']],
    [8, ['Boxe fitness', 'Condicionamento', 'Defesa pessoal']],
    [9, ['Zumba', 'Ritmos latinos', 'Coreografia']],
    [10, ['Cycling', 'Resistência', 'Interval training']],
];
$out[] = 'INSERT INTO trainer_specializations (trainer_id, name) VALUES';
$specRows = [];
foreach ($specs as [$tid, $names]) {
    foreach ($names as $n) {
        $specRows[] = sprintf('    (%d, \'%s\')', $tid, sqlStr($n));
    }
}
$out[] = implode(",\n", $specRows) . ';';
$out[] = '';

$certs = [
    [1, 'Instrutora de Yoga RYT-200'],
    [2, 'Pilates Mat — Nível Avançado'],
    [3, 'Zumba Basic 1 License'],
    [4, 'Personal Trainer Certificado'],
    [5, 'Yoga Alliance 200h'],
    [6, 'NSCA CPT'],
    [7, 'Pilates Reformer'],
    [8, 'Boxe Fitness Instructor'],
    [9, 'Aeróbica em Grupo'],
    [10, 'Cycling Indoor Instructor'],
];
$out[] = 'INSERT INTO trainer_certifications (trainer_id, name) VALUES';
$certRows = [];
foreach ($certs as [$tid, $name]) {
    $certRows[] = sprintf('    (%d, \'%s\')', $tid, sqlStr($name));
}
$out[] = implode(",\n", $certRows) . ';';
$out[] = '';

$out[] = 'INSERT INTO classes (title, description, type, trainer_id, day_of_week, start_time, end_time, capacity) VALUES';
$classRows = [];
foreach ($classes as $c) {
    $classRows[] = sprintf(
        "    ('%s', '%s', '%s', %d, '%s', '%s', '%s', %d)",
        sqlStr($c[0]),
        sqlStr($c[1]),
        sqlStr($c[2]),
        $c[3],
        $c[4],
        $c[5],
        $c[6],
        $c[7]
    );
}
$out[] = implode(",\n", $classRows) . ';';
$out[] = '';

$out[] = "UPDATE classes SET featured = 1 WHERE title IN ('Yoga Matinal', 'Zumba Energy', 'Vibe Cycle');";
$out[] = "UPDATE users SET featured = 1 WHERE username IN ('ana.silva', 'ricardo.pereira', 'marco.rebelo');";
$out[] = '';

$out[] = 'INSERT INTO equipment (name, type, area, status) VALUES';
$equipment = [
    ['Banco de supino reto', 'strength', 'available'],
    ['Banco de supino inclinado', 'strength', 'available'],
    ['Smith machine', 'strength', 'in_use'],
    ['Rack de agachamento', 'strength', 'available'],
    ['Leg press 45°', 'strength', 'in_use'],
    ['Leg press horizontal', 'strength', 'available'],
    ['Hack squat', 'strength', 'available'],
    ['Cadeira extensora', 'strength', 'in_use'],
    ['Cadeira flexora', 'strength', 'available'],
    ['Prensa de ombros', 'strength', 'available'],
    ['Multipower', 'strength', 'in_use'],
    ['Banco de pesos livres', 'strength', 'available'],
    ['Passadeira elétrica 1', 'cardio', 'in_use'],
    ['Passadeira elétrica 2', 'cardio', 'available'],
    ['Passadeira elétrica 3', 'cardio', 'in_use'],
    ['Bicicleta estática A', 'cardio', 'available'],
    ['Bicicleta estática B', 'cardio', 'available'],
    ['Bicicleta estática C', 'cardio', 'maintenance'],
    ['Elíptica 1', 'cardio', 'in_use'],
    ['Elíptica 2', 'cardio', 'available'],
    ['Remo ergómetro', 'cardio', 'available'],
    ['Escada rolante', 'cardio', 'maintenance'],
    ['Bike de spinning 1', 'cardio', 'in_use'],
    ['Bike de spinning 2', 'cardio', 'available'],
    ['Cabos crossover', 'functional', 'available'],
    ['Kettlebells rack', 'functional', 'available'],
    ['TRX suspension trainer', 'functional', 'available'],
    ['Bosu balance trainer', 'functional', 'in_use'],
    ['Medicine balls rack', 'functional', 'available'],
    ['Saco de boxe pesado', 'functional', 'in_use'],
];
$eqRows = [];
foreach ($equipment as $e) {
    $eqRows[] = sprintf("    ('%s', '%s', 'main', '%s')", sqlStr($e[0]), sqlStr($e[1]), sqlStr($e[2]));
}
$out[] = implode(",\n", $eqRows) . ';';
$out[] = '';

$memberIdStart = $trainerCount + 1;
$memberIds = range($memberIdStart, $memberIdStart + count($members) - 1);
$classCount = count($classes);

// Enrollments: each class gets 10-14 random members (deterministic shuffle by class id)
$enrollments = [];
foreach (range(1, $classCount) as $classId) {
    $pool = $memberIds;
    $seed = $classId * 17;
    usort($pool, static function (int $a, int $b) use ($seed): int {
        return (($a * $seed) % 97) <=> (($b * $seed) % 97);
    });
    $take = min(count($pool), 10 + ($classId % 5));
    foreach (array_slice($pool, 0, $take) as $uid) {
        $enrollments[] = [$uid, $classId];
    }
}
// Extra fill for HIIT Sunrise (class 7) — almost full
foreach (array_slice($memberIds, 0, 9) as $uid) {
    $pair = [$uid, 7];
    if (!in_array($pair, $enrollments, true)) {
        $enrollments[] = $pair;
    }
}
$enrollmentSet = [];
$enrollmentKeys = [];
foreach ($enrollments as [$uid, $cid]) {
    $key = $uid . ':' . $cid;
    if (isset($enrollmentKeys[$key])) {
        continue;
    }
    $enrollmentKeys[$key] = true;
    $enrollmentSet[$cid][] = $uid;
}
$out[] = 'INSERT INTO enrollments (user_id, class_id) VALUES';
$enrRows = [];
foreach (array_keys($enrollmentKeys) as $key) {
    [$uid, $cid] = array_map('intval', explode(':', $key));
    $enrRows[] = sprintf('    (%d, %d)', $uid, $cid);
}
$out[] = implode(",\n", $enrRows) . ';';
$out[] = '';

$out[] = 'INSERT INTO class_waitlist (user_id, class_id) VALUES';
$out[] = '    (' . $memberIds[0] . ', 7),';
$out[] = '    (' . $memberIds[5] . ', 7);';
$out[] = '';

// Reviews: at least 7 per class; distribuição natural com média global ≈ 4,5
$reviewSlots = [];
foreach (range(1, $classCount) as $classId) {
    $enrolled = $enrollmentSet[$classId] ?? [];
    if ($enrolled === []) {
        continue;
    }
    $reviewCount = min(count($enrolled), 7 + ($classId % 4));
    $pool = $enrolled;
    usort($pool, static function (int $a, int $b) use ($classId): int {
        return (($a + $classId * 3) % 53) <=> (($b + $classId * 3) % 53);
    });
    for ($r = 0; $r < $reviewCount; $r++) {
        $uid = $pool[$r % count($pool)];
        $reviewSlots[] = [$uid, $classId, $r];
    }
}

$uniqueSlots = [];
$seenSlots = [];
foreach ($reviewSlots as [$uid, $classId, $r]) {
    $key = $uid . ':' . $classId;
    if (isset($seenSlots[$key])) {
        continue;
    }
    $seenSlots[$key] = true;
    $uniqueSlots[] = [$uid, $classId, $r];
}

$ratingPool = buildNaturalReviewRatings(count($uniqueSlots));

$out[] = 'INSERT INTO reviews (user_id, class_id, rating, comment) VALUES';
$revRows = [];
foreach ($uniqueSlots as $index => [$uid, $classId, $r]) {
    $rating = $ratingPool[$index];
    $comments = $reviewComments[$rating];
    $comment = $comments[($uid + $classId + $r) % count($comments)];
    $revRows[] = sprintf("    (%d, %d, %d, '%s')", $uid, $classId, $rating, sqlStr($comment));
}
$out[] = implode(",\n", $revRows) . ';';

echo implode("\n", $out) . "\n";
