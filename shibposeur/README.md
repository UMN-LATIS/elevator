ShibPoseur
==========

A PHP script that pretends to be a limited Shibboleth Native SP at /Shibboleth.sso

ShibPoseur is intended for developers working on web applications which
authenticate via a [Shibboleth Native SP](http://shibboleth.net/).  

It allows developers to setup a simple environment for local development in
Apache which mimics certain core behaviors of a real Shibboleth Service Provider, without
needing to actually install `shibd` and configure it to access an Identity
Provider.

##Requirements
- Apache web server
- `mod_rewrite`
- The ability to create a .htaccess file at the site document root or modify the
  `VirtualHost` configuration to add rewrite rules

##Setup
When used with PHP applications, it is recommended to use Apache's
`auto_prepend_file` directive to force file inclusion on the Shibboleth.sso.php
file to ensure that an already authenticated Shibboleth environment is populated
into the `$_SERVER` superglobal.


Configure your Apache web server to route Shibboleth session handler requests to
a PHP script.

### Example .htaccess placed at the site document root

```apache
# For PHP applications, force inclusion on the ShibPoseur controller file
# to ensure the Shib attributes get populated even if you didn't bounce through
# the session initiator
php_value auto_prepend_file "/path/to/shibposeur/Shibboleth.sso.php"

RewriteEngine On
# Route requests to the session handler 
RewriteRule ^Shibboleth.sso/(?:(.*)) shibposeur/Shibboleth.sso.php?shibaction=$1 [L,QSA]
```

### Example Apache VirtualHost Rewrites

```apache
php_value auto_prepend_file "/path/to/shibposeur/Shibboleth.sso.php"

RewriteEngine On
# Route requests to the session handler 
RewriteRule ^/Shibboleth.sso/(?:(.*)) /shibposeur/Shibboleth.sso.php?shibaction=$1 [L,QSA]
```

Other programming languages can  populate the environment by decoding a JSON
string (base64-encoded) stored in a cookie after logging in.

The name of the cookie set by ShibPoseur consists of the prefix `_shibposeur_` followed 
by the hostname sent with the HTTP request, whereby dots have been replaced by underscores.

For example, the cookie ShibPoseur will set for `www.example.com` would be:

    _shibposeur_www_example_com

For example in Ruby on Rails application, you might add a `before_filter` to the
`application_controller.rb`:

```ruby
# In app/controllers/application_controller.rb:

before_filter :shibposeur_load

def shibposeur_load
  if Rails.env == 'development'
    cook = cookies['_shibposeur_' + request.host.gsub('.', '_')]
    shibjson = Base64.decode64(cook)
    if shibjson
      shibparams = JSON.parse(shibjson)

      # NOTE: This does not validate `key` against a whitelist of acceptable
      # values! That is really important (and handled natively by the PHP
      # version) and I'll add it when I have time, or build this as a proper
      # Ruby module or Gem
      shibparams.each do |key, val|
        ENV[key] = val 
      end
    end 
  end 
end 
```
