class { 'nubis_apache':
  # Changing the Apache mpm is necessary for the Apache PHP module
  mpm_module_type => 'prefork',
  check_url       => '/favicon.ico',
}

# Add modules
class { 'apache::mod::rewrite': }
class { 'apache::mod::php': }

apache::vhost { $project_name:
  port               => 80,
  default_vhost      => true,
  docroot            => "/var/www/${project_name}",
  docroot_owner      => 'root',
  docroot_group      => 'root',
  block              => ['scm'],
  setenvif           => [
    'X-Forwarded-Proto https HTTPS=on',
    'Remote_Addr 127\.0\.0\.1 internal',
    'Remote_Addr ^10\. internal',
  ],
  access_log_env_var => '!internal',
  access_log_format  => '%a %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-agent}i\"',
  custom_fragment    => "
        # Don't set default expiry on anything
        ExpiresActive Off

        # Clustered without coordination
        FileETag None

        ${::nubis::apache::sso::custom_fragment}
  ",

  directories        => [
    {
      'path'      => "/var/www/${project_name}",
      'provider'  => 'directory',
      'auth_type' => 'openid-connect',
      'require'   => 'valid-user',
    },
    {
      'path'      => '/favicon.ico',
      'provider'  => 'location',
      'auth_type' => 'None',
      'require'   => 'all granted',
    },
  ],

  headers            => [
    # Nubis headers
    "set X-Nubis-Version ${project_version}",
    "set X-Nubis-Project ${project_name}",
    "set X-Nubis-Build   ${packer_build_name}",

    # Security Headers
    'set X-Content-Type-Options "nosniff"',
    'set X-XSS-Protection "1; mode=block"',
    'set X-Frame-Options "DENY"',
    'set Strict-Transport-Security "max-age=31536000"',
  ],

  rewrites           => [
    {
      comment      => 'HTTPS redirect',
      rewrite_cond => ['%{HTTP:X-Forwarded-Proto} =http'],
      rewrite_rule => ['. https://%{HTTP:Host}%{REQUEST_URI} [L,R=permanent]'],
    }
  ]
}

file { "/var/www/${project_name}/config.php":
  source => 'puppet:///nubis/files/config.php', #lint:ignore:puppet_url_without_modules
  owner  => 'root',
  group  => 'root',
  mode   => '0644',
}

file { '/etc/confd':
  ensure  => directory,
  recurse => true,
  purge   => false,
  owner   => 'root',
  group   => 'root',
  source  => 'puppet:///nubis/files/confd',
}

include nubis_configuration
nubis::configuration{ $project_name:
  format  => 'php',
}

package { 'php-mail':
  ensure => 'latest',
}

package { 'php-ldap':
  ensure => 'latest',
}

package {
      'php-mysql':
  ensure => 'latest',
}
