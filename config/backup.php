<?php

return [

    'backup' => [

        // Nome usado no nome do arquivo do backup e nas notificações.
        'name' => env('APP_NAME', 'IgnControl'),

        'source' => [
            'files' => [
                // Documentos anexados (PDFs de consulta CNPJ, documentos de
                // contato) — vivem em storage/app/private (disco "local").
                'include' => [
                    storage_path('app/private'),
                ],

                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                ],

                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => null,
            ],

            // Conexão configurada em config/database.php ("mysql" em
            // produção, ignorada em dev onde o padrão é sqlite).
            'databases' => [
                'mysql',
            ],
        ],

        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => null,
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => \ZipArchive::CM_DEFAULT,
            'compression_level' => 9,
            'filename_prefix' => '',

            // Disco dedicado (config/filesystems.php), fora de
            // storage/app/private pra não entrar em loop.
            'disks' => [
                'backups',
            ],
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',

        'tries' => 1,
        'retry_delay' => 0,
    ],

    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            // O pacote exige um e-mail válido aqui mesmo que a notificação
            // por e-mail nunca seja realmente usada (ver comentário acima:
            // sem MAIL_* configurado, o backup roda normalmente e só não
            // manda aviso nenhum). Por isso o fallback fixo.
            'to' => env('BACKUP_NOTIFICATION_EMAIL', 'backup@ignf.local'),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'IgnControl'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],
    ],

    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'IgnControl'),
            'disks' => ['backups'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            // Combinado com o Rodrigo: 30 dias de backups diários + mensal
            // por 12 meses. As faixas "all" e "weekly" são a estratégia
            // padrão do pacote pra suavizar a transição entre diário e
            // mensal — não atrapalham o combinado, só adicionam cobertura.
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 30,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 12,
            'keep_yearly_backups_for_years' => 0,

            // Trava de segurança: se a pasta de backups passar de 10GB,
            // apaga os mais antigos primeiro, antes de aplicar as regras
            // acima.
            'delete_oldest_backups_when_using_more_megabytes_than' => 10000,
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],

];
