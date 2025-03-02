<?php
namespace Deployer;

require 'recipe/codeigniter.php';

// Config

set('repository', 'git@github.com:UMN-LATIS/elevator.git');
set('update_code_strategy', 'clone');
set('shared_files', ['.env']);
add('shared_dirs', []);
add('writable_dirs', ['application/models/Proxies']);
set('keep_releases', 5);
// Hosts

host('cla-dev')
    ->setHostname('cla-dev.elevatorapp.net')
->setLabels(['stage' => 'cla_dev'])
    ->set('remote_user', 'latis_deploy')
    ->set('deploy_path', '/var/www/elevator');

host('dev')
    ->setHostname('dev.elevator.umn.edu')
    ->set('remote_user', 'latis_deploy')
    ->set('deploy_path', '/var/www/elevator');

host('umn')
    ->setHostname('52.207.134.157')
    ->set('stage', 'production')
    ->set('remote_user', 'latis_deploy')
    ->set('deploy_path', '/var/www/elevator');


// Hooks


before('deploy:writable', 'elevator:create_proxies');
// tasks
task('elevator:create_proxies', function () {
    run('cd {{release_path}} && mkdir -p application/models/Proxies');
});

// after vendor install, run npm install and gulp
task('deploy:assets', function () {
    run('cd {{release_path}} && npm install');
    run('cd {{release_path}} && ./node_modules/.bin/gulp');
});
after('deploy:vendors', 'deploy:assets');

task('elevator:restart_systemd', function() {
    run('sudo systemctl restart migrateCollections');
    run('sudo systemctl restart populateCacheTube');
    run('sudo systemctl restart prepareDrawers');
    run('sudo systemctl restart updateIndexes');
    run('sudo systemctl restart urlImport');
    run('sudo systemctl restart restoreFiles');
});

after('deploy:update_code', 'deploy:git:submodules');
task('deploy:git:submodules', function () {
    $git = get('bin/git');

    cd('{{release_path}}');
    run("$git submodule update --init");
});

after('deploy:git:submodules', 'elevator:build-ui');
task('elevator:build-ui', function () {
    run('cd {{release_path}}/assets/elevator-ui && yarn install');
    run('cd {{release_path}}/assets/elevator-ui && yarn build:prod');
});

after('elevator:build-ui', 'elevator:create_instance_assets');
task('elevator:create_instance_assets', function () {
    run('cd {{release_path}}/assets/ && mkdir instanceAssets && chmod 777 instanceAssets');
});

after('deploy:symlink', 'elevator:restart_systemd');


// TODO: consider logic that runs migrations and if migrations have run, flushdb for redis. or at least clear all the doctrine cachines?

after('deploy:failed', 'deploy:unlock');
