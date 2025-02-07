<?php
namespace Deployer;

require 'recipe/codeigniter.php';

// Config

set('repository', 'git@github.com:UMN-LATIS/elevator.git');

set('shared_files', ['.env']);
add('shared_dirs', []);
add('writable_dirs', ['application/models/Proxies']);

// Hosts

host('cla-dev.elevatorapp.net')
    ->set('remote_user', 'latis_deploy_user')
    ->set('deploy_path', '/var/www/elevator');

// Hooks


after('deploy:failed', 'deploy:unlock');
