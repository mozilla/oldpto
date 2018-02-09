# XXX: This should live in nubis_apache eventually

# Stub class for SSO
class nubis::apache::sso(
  $mod_auth_openidc_version = '2.3.3',
  $libcjose_version = '0.5.1',
){

  $custom_fragment = "
OIDCResponseType 'code'
OIDCScope 'openid email profile'
OIDCOAuthRemoteUserClaim email
OIDCRemoteUserClaim email
OIDCOAuthTokenExpiryClaim exp absolute mandatory
OIDCPassIDTokenAs claims serialized
OIDCOAuthTokenIntrospectionInterval 15
OIDCUserInfoRefreshInterval 15
OIDCSessionMaxDuration 0
OIDCSessionInactivityTimeout 43200
OIDCOutgoingProxy proxy.service.consul:3128
"

  # Install mod_auth_openidc and dependency
  package { 'libjansson4':
    ensure => installed,
  }->
  package { 'libhiredis0.13':
    ensure => installed,
  }->
  package { 'libcurl3':
    ensure => installed,
  }->
  package { 'memcached':
    ensure => installed,
  }->
  staging::file { 'libcjose0.deb':
    source => "https://github.com/zmartzone/mod_auth_openidc/releases/download/v2.3.0/libcjose0_${libcjose_version}-1.${::lsbdistcodename}.1_${::architecture}.deb"
  }->
  package { 'libcjose0':
    ensure   => installed,
    provider => dpkg,
    source   => '/opt/staging/libcjose0.deb'
  }->
  staging::file { 'mod_auth_openidc.deb':
    source => "https://github.com/zmartzone/mod_auth_openidc/releases/download/v${mod_auth_openidc_version}/libapache2-mod-auth-openidc_${mod_auth_openidc_version}-1.${::lsbdistcodename}.1_${::architecture}.deb",
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

}

# Just invoke it
class { 'nubis::apache::sso':

}
