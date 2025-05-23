# Virtual Machine Configuration

This VM is the core of my home lab infrastructure. It's running on **VMware Workstation** and serves as the host for my self-hosted services (like Nextcloud, Nginx, MySQL, Redis, etc).

## System Specs

- **OS**: Ubuntu 24.04.2 LTS
- **RAM**: 4 GB
- **CPU**: 2 vCPUs
- **Storage**: 300 GB
- **Network**: Bridged (visible on LAN)

## Open Ports (Firewall + Router)

| Service       | Port(s)    | Protocol | Description             |
|---------------|------------|----------|-------------------------|
| SSH           | 22         | TCP      | Remote terminal access  |
| Nginx         | 80/443     | TCP      | Web access (HTTP/HTTPS) |
| MySQL         | 3306       | TCP      | Database                |
| Redis         | 6379       | TCP      | Caching / KV storage    |

All the above ports are **allowed in UFW** (Ubuntu firewall) and **forwarded in the router** to expose the VM to the local network and optionally internet (for select services).

## Security Hardening

| Feature     | Status | Description |
|-------------|--------|-------------|
| **SSH Access** | Enabled | SSH login is enabled. Only key-based auth is recommended. |
| **UFW Firewall** | Active | Configured to allow only necessary ports. |
| **Fail2Ban** | Installed & Active | Protects against brute-force attacks via SSH and other services. |
| **Root Login** | Disabled | Root login over SSH is disabled for extra safety. |

## Network Notes

- The **bridged network** config gives the VM a LAN IP, so it's accessible like a separate machine.
- Useful for running internal services and accessing them from mobile/PCs on the same Wi-Fi.

