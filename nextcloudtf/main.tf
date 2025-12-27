resource "helm_release" "nextcloud" {
  name       = "nextcloud-${random_id.suffix.hex}"
  repository = "https://nextcloud.github.io/helm/"
  chart      = "nextcloud"
  version    = "8.7.0"
  namespace  = "nextcloud"
  create_namespace = true 

  values = [
    templatefile("${path.module}/values.yaml", {
      admin_password = var.admin_password
      db_password    = var.db_password
      nextcloud_host = var.nextcloud_host
    })
  ]

  set {
    name  = "nextcloud.trustedDomains[0]"
    value = var.nextcloud_host
  }

  wait          = true
  timeout       = 600
  atomic        = true 
  cleanup_on_fail = true
}

resource "random_id" "suffix" {
  byte_length = 4 
}

