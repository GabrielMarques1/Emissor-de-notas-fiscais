<?php

namespace Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\HotReloader\HotReloader;
use App\Libraries\AutoSync;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

Events::on('pre_system', static function () {
    if (ENVIRONMENT !== 'testing') {
        if (ini_get('zlib.output_compression')) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn ($buffer) => $buffer);
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        Services::toolbar()->respond();
        // Hot Reload route - for framework use on the hot reloader.
        if (ENVIRONMENT === 'development') {
            Services::routes()->get('__hot-reload', static function () {
                (new HotReloader())->run();
            });
        }
    }

    // --------------------------------------------------------------------
    // Auto-run database migrations on web requests (non-CLI)
    // --------------------------------------------------------------------
    // Ensures new installations or updates apply pending migrations
    // without requiring manual `php spark migrate`.
    try {
        if (! is_cli()) {
            $migrationsConfig = config('Migrations');
            if ($migrationsConfig && $migrationsConfig->enabled === true) {
                // Initialize database connection early; skip if it fails
                $db = \Config\Database::connect();
                $db->initialize();

                $migrator = \Config\Services::migrations();
                // Limit to application namespace to avoid vendor migrations unless desired
                if (method_exists($migrator, 'setNamespace')) {
                    $migrator->setNamespace('App');
                }
                $migrator->latest();
            }
        }
    } catch (\Throwable $e) {
        log_message('error', 'Auto-migration failed: {message}', ['message' => $e->getMessage()]);
    }
});

// Sincronização automática e backup periódico ao final de cada resposta web
Events::on('post_system', static function () {
    if (! is_cli()) {
        AutoSync::maybeSync();
        AutoSync::maybeDailyBackup();
    }
});
