resource "helm_release" "wireguard" {
  name = "wireguard-${random_id.suffix.hex}"
  repository = "https://bryopsida.github.io/wireguard-chart"
  chart = "wireguard"
  version = "latest"
  namespace = "wireguard"
  create_namespace = true

  values = [
    templatefile("${path.module}/values.yaml", {})
  ]

  wait = true
  timeout = 600
  atomic = true
  cleanup_on_fail = true
}
resource "random_id" "suffix" {
  byte_length = 4
}
