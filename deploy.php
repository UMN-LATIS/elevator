<?php
namespace Deployer;

require 'recipe/codeigniter.php';

set('cache_secret', file_get_contents('.cache_secret'));
if(!get('cache_secret')) {
    echo "You must set the cache secret\n";
    die();
}
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
    ->set('reset_path', "https://dev.elevator.umn.edu/defaultinstance/home/flushCache/")
    ->set('deploy_path', '/var/www/elevator');


host('umn')
    ->setHostname('beta.elevator.umn.edu')
    ->setLabels(['stage'=>'production'])
    ->set('remote_user', 'latis_deploy')
    ->set('reset_path', "https://dcl.elevator.umn.edu/home/flushCache/")
    ->set('deploy_path', '/var/www/elevator');

host('olaf')
    ->setHostname('digital.stolaf.edu')
    ->setLabels(['stage'=>'production'])
    ->set('remote_user', 'latis_deploy')
    ->set('reset_path', "https://digital.stolaf.edu/home/flushCache/")
    ->set('deploy_path', '/var/www/elevator');

    host('ou')
    ->setHostname('3d.libraries.ou.edu')
    ->setLabels(['stage'=>'production'])
    ->set('remote_user', 'latis_deploy')
    ->set('reset_path', "https://3d.libraries.ou.edu/home/flushCache/")
    ->set('deploy_path', '/var/www/elevator');

host('bennington')
    ->setHostname('elevator.bennington.edu')
    ->setLabels(['stage'=>'production'])
    ->set('remote_user', 'latis_deploy')
    ->set('reset_path', "https://elevator.bennington.edu/home/flushCache/")
    ->set('deploy_path', '/var/www/elevator');

host('stthomas')
    ->setHostname('elevator.stthomas.edu')
    ->setLabels(['stage'=>'production'])
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
    run('sudo /usr/local/bin/restart_services.sh restart');
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

after('deploy:symlink', 'elevator:clear_cache');
task('elevator:clear_cache', function () {
    runLocally('curl -s {{reset_path}}' . get('cache_secret'));
});

after('deploy:failed', 'deploy:unlock');
