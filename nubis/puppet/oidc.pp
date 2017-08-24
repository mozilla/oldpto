# Install mod_auth_openidc and dependency
package { 'libjansson4':
  ensure => installed,
}->
package { 'libhiredis0.10':
  ensure => installed,
}->
package { 'memcached':
  ensure => installed,
}->
staging::file { 'libcjose0.deb':
  source => "https://github.com/pingidentity/mod_auth_openidc/releases/download/v${mod_auth_openidc_version}/libcjose0_${libcjose_version}-1.${::lsbdistcodename}.1_${::architecture}.deb",
}->
package { 'libcjose0':
  ensure   => installed,
  provider => dpkg,
  source   => '/opt/staging/libcjose0.deb'
}->
staging::file { 'mod_auth_openidc.deb':
  source => "https://github.com/pingidentity/mod_auth_openidc/releases/download/v${mod_auth_openidc_version}/libapache2-mod-auth-openidc_${mod_auth_openidc_version}-1.${::lsbdistcodename}.1_${::architecture}.deb",
}->
package { 'mod_auth_openidc':
  ensure   => installed,
  provider => dpkg,
  source   => '/opt/staging/mod_auth_openidc.deb',
  require  => [
    Class['Nubis_apache'],
  ]
}->
apache::mod { 'auth_openidc': }
