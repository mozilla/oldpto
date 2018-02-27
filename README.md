# PTO Nubis deployment repository

This is the deployment repository for
[pto.mozilla.org](https://pto.mozilla.org)

## Components

Defined in [nubis/terraform/main.tf](nubis/terraform)

### Webservers

Defined in [nubis/puppet/apache.pp](nubis/puppet)

The produced image is that of a simple Ubuntu Apache webserver running PHP

### Load Balancer

Simple ELB

### Email

This application sends outbound e-mails using SES

### SSO

This entire application is protected behind [mod_auth_openidc](https://github.com/zmartzone/mod_auth_openidc)

### Database

Main application state is persisted in an RDS/MySQL database

Administrative access to it can be gained thru the db-admin service.

### Cache

Elasticache/Memcache is used to provide persistency for
[mod_auth_openidc](https://github.com/zmartzone/mod_auth_openidc)'s session cache

## Configuration

The application's configuration file is
[config.php](nubis/puppet/files/config.php)
and is not managed, it simply sources nubis_configuration
from */etc/nubis-config/${project_name}.php*

### Consul Keys

This application's Consul keys, living under
*${project_name}-${environment}/${environment}/config/*
and defined in Defined in [nubis/terraform/consul.tf](nubis/terraform)

#### Debug

*Operator Supplied* Controls an application-specific debugging mode

#### export_users

*Operator Supplied* List of email addresses of users allowed to export reports

#### hr_managers

*Operator Supplied* List of email addresses of HR managers

#### mail_blacklist

*Operator Supplied* List of email addresses where mail may **NOT** be sent

#### mail_submitter

*Operator Supplied* Full e-mail address of the sender of PTO emails

#### notified_people

*Operator Supplied* Full e-amil address that will always recieve PTO emails

#### ldap_host

*Operator Supplied* LDAP Url to connect to the server, for example

```
ldaps://ldap.company.com:636
```

#### ldal_bind_user

*Operator Supplied* Bind DN to use to authenticate to the LDAP server

#### ldap_bind_pass

*Operator Supplied* Password to use to authenticate to the LDAP server

#### Cache/Endpoint

DNS endpoint of Elasticache/memcache

#### Cache/Port

TCP port of Elasticache/memcache

The hostname of the RDS/MySQL Database

#### OpenID/Server/Memcached

Hostname:Port of Elasticache/memcache

#### OpenID/Server/Passphrase

*Generated* OpenID passphrase for session encryption

#### OpenID/Client/Domain

*Operator Supplied* Auth0 Domain for this application, typically 'mozilla'

#### OpenID/Client/ID

*Operator Supplied* Auth0 Client ID for this application

#### OpenID/Client/Secret

*Operator Supplied* Auth0 Client Secret for this application 'mozilla'

#### OpenID/Client/Site

*Operator Supplied* Auth0 Site URL for this application

#### SMTP/Server

SES SMTP server hostname

#### SMTP/User

SES SMTP username

#### SMTP/Password

SES SMTP password

## Cron Jobs

Daily backup job copies data from [Storage](#storage) to [Buckets](#buckets)

## Logs

No application specific logs
