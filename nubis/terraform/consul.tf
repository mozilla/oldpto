# Discover Consul settings
module "consul" {
  source       = "github.com/nubisproject/nubis-terraform//consul?ref=v2.0.4"
  region       = "${var.region}"
  environment  = "${var.environment}"
  account      = "${var.account}"
  service_name = "${var.service_name}"
}

# Configure our Consul provider, module can't do it for us
provider "consul" {
  address    = "${module.consul.address}"
  scheme     = "${module.consul.scheme}"
  datacenter = "${module.consul.datacenter}"
}

resource "random_id" "openid_server_passphrase" {
  byte_length = 16
}

# Publish our outputs into Consul for our application to consume
resource "consul_keys" "config" {
  key {
    name   = "openid_server_passphrase"
    path   = "${module.consul.config_prefix}/OpenID/Server/Passphrase"
    value  = "${random_id.openid_server_passphrase.b64_url}"
    delete = true
  }
}
