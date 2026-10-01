<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default content of the public landing page
    |--------------------------------------------------------------------------
    |
    | These texts are shown until the administrator saves their own from
    | Configuración → Página de inicio. The lists keep their length: the icon
    | of each card is chosen by its position.
    |
    */

    'contact' => [
        'phone' => '(01) 000-0000',
        'email' => 'contacto@medicosintegrados.test',
        'address' => 'Dirección de la clínica',
    ],

    'about' => [
        'title' => 'Un centro médico que te conoce y te cuida',
        'paragraph_one' => 'En Médicos Integrados reunimos médicos de distintas especialidades y un equipo administrativo comprometido para que recibas atención completa en un mismo lugar, sin vueltas ni papeleo.',
        'paragraph_two' => 'Creemos que la buena medicina combina el criterio del médico con información ordenada y accesible. Por eso tu historial, tus citas y tus pagos están conectados y siempre a tu alcance.',
        'mission' => 'Acompañar a cada paciente y a su familia con atención médica integral, cercana y segura.',
    ],

    'values' => [
        [
            'title' => 'Cercanía',
            'description' => 'Te escuchamos y te acompañamos en cada etapa de tu atención, con un trato humano y respetuoso.',
        ],
        [
            'title' => 'Confianza',
            'description' => 'Cuidamos tu información clínica con cifrado y acceso solo para el personal autorizado.',
        ],
        [
            'title' => 'Tecnología útil',
            'description' => 'Digitalizamos lo que te quita tiempo para que tu médico se concentre en ti.',
        ],
    ],

    'services' => [
        'title' => 'Atención para cada momento de tu salud',
        'intro' => 'Desde una consulta rutinaria hasta el seguimiento de un tratamiento, te acompañamos con un equipo médico multidisciplinario.',
        'items' => [
            [
                'title' => 'Consulta médica general',
                'description' => 'Evaluación integral, diagnóstico y orientación para resolver tus molestias del día a día.',
            ],
            [
                'title' => 'Atención por especialidades',
                'description' => 'Médicos especialistas para atender tu caso con el enfoque que necesita.',
            ],
            [
                'title' => 'Chequeos y controles preventivos',
                'description' => 'Revisiones periódicas para cuidar tu salud antes de que aparezcan los problemas.',
            ],
            [
                'title' => 'Seguimiento de tratamientos',
                'description' => 'Control de tu evolución consulta a consulta, con tu historial siempre a la mano.',
            ],
            [
                'title' => 'Recetas médicas',
                'description' => 'Indicaciones y medicamentos claros, en un documento que puedes recibir por correo.',
            ],
            [
                'title' => 'Historia clínica',
                'description' => 'Tus consultas, antecedentes y recetas organizados, disponibles para ti y tu médico.',
            ],
        ],
    ],

];
