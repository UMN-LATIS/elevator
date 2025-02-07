<?php
namespace Deployer;

require 'recipe/codeigniter.php';

// Config

set('repository', 'git@github.com:UMN-LATIS/elevator.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('cla-dev.elevatorapp.net')
    ->set('remote_user', 'latis_deploy_user')
    ->set('deploy_path', '/var/www/elevator');

// Hooks

after('deploy:failed', 'deploy:unlock');
