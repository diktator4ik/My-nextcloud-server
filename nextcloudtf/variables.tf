variable "admin_password" {
  description = "Nextcloud admin pass"
  type      = string
  sensitive = true
}

variable "db_password" {
  description = "PostgreSQL nextcloud admin pass" 
  type      = string
  sensitive = true
}

variable "nextcloud_host" {
  description = "host dns for nextcloud"
  type = string
  sensitive = false
}
