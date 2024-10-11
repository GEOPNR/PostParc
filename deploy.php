<?php
namespace Deployer;

require 'recipe/symfony.php';

// Project name
set('application', 'postparcv2');

// Symfony build set
set('symfony_env', 'prod');

// Project repository
set('repository', 'ssh://gitolite@projet.probesys.com/postparc/postparcv2.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true); 

// Shared files/dirs between deploys 
set('shared_files', ['app/config/parameters.yml','app/config/parameters_postparc.yml','app/config/config_*.yml']);
add('shared_dirs', ['var/logs','.git']);

// Writable dirs by web server 
add('writable_dirs', ['var/cache', 'var/logs']);
// Clear paths
set('clear_paths', ['web/app_*.php', 'web/config.php']);
// Assets
set('assets', ['web/css', 'web/images', 'web/js', 'web/assets', 'web/js', 'web/bundles', 'web/documentTemplateImages', 'web/files', 'web/uploads', 'web/importModels']);
// Environment vars
set('env', function () {
    return [
        'SYMFONY_ENV' => get('symfony_env')
    ];
});
set('composer_options', function () {
    $debug = get('symfony_env') === 'dev';
    return sprintf('{{composer_action}} --verbose --prefer-dist --no-progress --no-interaction %s --optimize-autoloader --no-suggest', (!$debug ? '--no-dev' : ''));
});

// Adding support for the Symfony3 directory structure
set('bin_dir', 'bin');
set('var_dir', 'var');
// Symfony console bin
set('bin/console', function () {
    return sprintf('{{release_path}}/%s/console', trim(get('bin_dir'), '/'));
});

// Symfony console opts
set('console_options', function () {
    $options = '--no-interaction --env={{symfony_env}}';
    return get('symfony_env') !== 'prod' ? $options : sprintf('%s -:qno-debug', $options);
});

// Migrations configuration file
set('migrations_config', '');

/**
 * Create cache dir
 */
task('deploy:create_cache_dir', function () {
    // Set cache dir
    set('cache_dir', '{{release_path}}/' . trim(get('var_dir'), '/') . '/cache');

    // Remove cache dir if it exist
    run('if [ -d "{{cache_dir}}" ]; then rm -rf {{cache_dir}}; fi');

    // Create cache dir
    run('mkdir -p {{cache_dir}}');

    // Set rights
    run("chmod -R g+w {{cache_dir}}");
})->desc('Create cache dir');

// change user if necessary
task('deploy:chguser', function () {      
    if (get('bash_user')) {
        run('chguser {{bash_user}}');
    }
    
})->desc('Define the user use for bash commands');

// get user info
task('deploy:whoami', function () {      
        run('whoami');
})->desc('get user info');


/**
 * Normalize asset timestamps
 */
task('deploy:assets', function () {
    $assets = implode(' ', array_map(function ($asset) {
        return "{{release_path}}/$asset";
    }, get('assets')));

    run(sprintf('find %s -exec touch -t %s {} \';\' &> /dev/null || true', $assets, date('YmdHi.s')));
})->desc('Normalize asset timestamps');


/**
 * Install assets from public dir of bundles
 */
task('deploy:assets:install', function () {
    run('{{bin/php}} {{bin/console}} assets:install {{console_options}} {{release_path}}/web');
})->desc('Install bundle assets');


task('deploy:update_code', function () {
    run('git clone {{repository}} {{release_path}}');
});

/**
 * Dump all assets to the filesystem
 */
task('deploy:assetic:dump', function () {
    if (get('dump_assets')) {
        run('{{bin/php}} {{bin/console}} assetic:dump {{console_options}}');
    }
})->desc('Dump assets');

/**
 * Clear Cache
 */
task('deploy:cache:clear', function () {
    run('{{bin/php}} {{bin/console}} cache:clear {{console_options}} --no-warmup');
})->desc('Clear cache');

/**
 * Warm up cache
 */
task('deploy:cache:warmup', function () {
    run('{{bin/php}} {{bin/console}} cache:warmup {{console_options}}');
})->desc('Warm up cache');


/**
 * Migrate database
 */
task('database:migrate', function () {
    $options = '{{console_options}} --allow-no-migration';
    if (get('migrations_config') !== '') {
        $options = sprintf('%s --configuration={{release_path}}/{{migrations_config}}', $options);
    }

    run(sprintf('{{bin/php}} {{bin/console}} doctrine:migrations:migrate %s', $options));
})->desc('Migrate database');

set('user', function () {
    return '{{bash_user}}';
});


set('allow_anonymous_stats', false);

// Hosts

host('production')
        ->hostname('postparc.fr')
        ->stage('production')
        ->roles('app')    
        ->forwardAgent()
        ->user('postparcv2')
        ->set('deploy_path', '~/www')
        ->set('bash_user','postparcv2')
        ;

host('preprod')
        ->hostname('p6-preprod-php73.probesys.net')
        ->stage('preproduction')
        ->roles('app')    
        ->forwardAgent()
        ->user('philippe.godot')
        ->set('deploy_path', '/var/www/postparc/html') 
        ->become('postparc')
        ->set('bash_user','postparc')
        ;


// Tasks
task('build', function () { 
  run('cd www && git pull');
  run('cd www && php7.3 composer.phar install');
  run('cd www/scripts && ./update_db.sh');
  run('cd www/scripts && ./cache_clear.sh');
});

/**
 * Main task
 */
task('deploy', [
    //'deploy:chguser',
    //'deploy:whoami',
    'deploy:prepare',
    'deploy:info',

    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:clear_paths',
    'deploy:create_cache_dir',
    'deploy:shared',
    'deploy:assets',
    'deploy:vendors',
//    'deploy:assets:install',
//    'deploy:assetic:dump',
//    'deploy:cache:clear',
//    'deploy:cache:warmup',
//    'deploy:writable',
//    'deploy:symlink',
    'deploy:unlock',
    //'deploy:cleanup',
])->desc('Deploy your project');

// Display success message on completion
after('deploy', 'success');
