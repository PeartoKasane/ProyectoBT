# Infraestructura del servidor (Debian GNU/Linux)

Guía de configuración del servidor donde se aloja SGRSI. Cubre RNF5 (firewall), RNF6 (respaldos), RNF7 (SSH), RNF17 y RNF18 (disponibilidad), RNF19 (arranque automático), RNF25 (Debian) y RNF26 (HTTP/HTTPS en la red institucional).

> **Alcance.** Todo lo de este documento son pasos **a ejecutar en el servidor**, como `root` (o con `sudo`). **Nada de esto está implementado en el código del repositorio**: el repositorio solo contiene la aplicación (`app/`, `public/`, `config/`, `baseDeDatos/`). Los comandos se probaron como guía para Debian 12 "bookworm" (PHP 8.2, MySQL 8.4 LTS, Apache 2.4); en otras versiones cambian los números de versión de los paquetes.

Valores de ejemplo que debe ajustar:

| Valor | Ejemplo | Significado |
|---|---|---|
| `RED_INST` | `192.168.1.0/24` | Subred de la institución desde donde se accede |
| `/var/www/sgrsi` | | Carpeta donde se despliega el proyecto |
| `sgrsi.institucion.local` | | Nombre del servidor en la red interna |

Requisitos de la aplicación (según el repositorio): PHP 8.0 o superior (usa `match` y tipos `mixed`) con las extensiones PDO MySQL y `mbstring`, MySQL, y Composer para `vlucas/phpdotenv`. La base se llama `PeartoS.A` (con punto: en SQL va entre comillas invertidas).

## 1. Paquetes e instalación de la aplicación

```bash
# EJECUTAR EN EL SERVIDOR
apt update && apt full-upgrade -y
apt install -y apache2 php libapache2-mod-php php-mysql php-mbstring php-xml unzip git composer ufw wget lsb-release gnupg

# MySQL no está en los repositorios de Debian: se agrega el repositorio oficial de MySQL (elegir "MySQL Server & Cluster" > mysql-8.4-lts)
wget https://dev.mysql.com/get/mysql-apt-config_0.8.33-1_all.deb    # ajuste el nombre al paquete vigente en https://dev.mysql.com/downloads/repo/apt/
dpkg -i mysql-apt-config_*_all.deb
apt update && apt install -y mysql-server

# Despliegue (ajuste el origen del repositorio)
git clone <URL_DEL_REPOSITORIO> /var/www/sgrsi
cd /var/www/sgrsi && composer install --no-dev --optimize-autoloader
```

Actualizaciones de seguridad automáticas (RNF18):

```bash
apt install -y unattended-upgrades
dpkg-reconfigure -plow unattended-upgrades
```

## 2. Base de datos

```bash
# EJECUTAR EN EL SERVIDOR
mysql_secure_installation          # root solo por socket, sin usuarios anónimos ni base de prueba
```

Verifique que MySQL escuche **solo en local** (MySQL solo local, RNF5). En Debian ya es el valor por defecto; confírmelo:

```bash
grep -rn "bind-address" /etc/mysql/ 
# Debe aparecer: bind-address = 127.0.0.1
ss -tlnp | grep 3306               # Debe mostrar 127.0.0.1:3306, nunca 0.0.0.0 ni [::]
```

Si hiciera falta, cree `/etc/mysql/mysql.conf.d/99-sgrsi.cnf`:

```ini
[mysqld]
bind-address = 127.0.0.1
skip-name-resolve
```

Cree la base, cargue el esquema y un usuario propio de la aplicación (el `.env.example` usa `root` sin clave, que no debe usarse en producción):

```bash
# EJECUTAR EN EL SERVIDOR
mysql < /var/www/sgrsi/baseDeDatos/schema.sql    # crea `PeartoS.A`, tablas y datos de prueba
mysql <<'SQL'
CREATE USER 'sgrsi'@'localhost' IDENTIFIED BY 'CAMBIAR_POR_UNA_CLAVE_LARGA';
GRANT SELECT, INSERT, UPDATE, DELETE ON `PeartoS.A`.* TO 'sgrsi'@'localhost';
FLUSH PRIVILEGES;
SQL
```

Nota: `schema.sql` también inserta usuarios de prueba. En producción elimínelos o cambie sus contraseñas (y cree el administrador real) antes de abrir el sistema.

## 3. Apache, DocumentRoot y `.env` fuera del alcance web (RNF25, RNF26)

El `DocumentRoot` debe ser **`public/`**. Todo lo demás (`.env`, `app/`, `config/`, `vendor/`, `baseDeDatos/`, `docs/`) queda fuera del alcance web, de modo que `https://servidor/.env` no existe.

```bash
# EJECUTAR EN EL SERVIDOR
# Permisos: el código no lo escribe el servidor web
chown -R root:www-data /var/www/sgrsi
find /var/www/sgrsi -type d -exec chmod 750 {} \;
find /var/www/sgrsi -type f -exec chmod 640 {} \;

# Credenciales de la base
cp /var/www/sgrsi/.env.example /var/www/sgrsi/.env
chown root:www-data /var/www/sgrsi/.env && chmod 640 /var/www/sgrsi/.env
nano /var/www/sgrsi/.env
```

Contenido de `.env` (variables que lee `config/config.php`):

```ini
DB_HOST="localhost"
DB_PUERTO="3306"
DB_USUARIO="sgrsi"
DB_CLAVE="LA_MISMA_CLAVE_DEL_PASO_2"
DB_NOMBRE="PeartoS.A"
```

Sitio virtual HTTP/HTTPS, `/etc/apache2/sites-available/sgrsi.conf`:

```apache
<VirtualHost *:80>
    ServerName sgrsi.institucion.local
    Redirect permanent / https://sgrsi.institucion.local/
</VirtualHost>

<VirtualHost *:443>
    ServerName sgrsi.institucion.local
    DocumentRoot /var/www/sgrsi/public

    SSLEngine on
    SSLCertificateFile    /etc/ssl/certs/sgrsi.crt
    SSLCertificateKeyFile /etc/ssl/private/sgrsi.key

    <Directory /var/www/sgrsi/public>
        Options -Indexes -FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>

    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"

    ErrorLog  ${APACHE_LOG_DIR}/sgrsi_error.log
    CustomLog ${APACHE_LOG_DIR}/sgrsi_access.log combined
</VirtualHost>
```

Certificado: use el emitido por la autoridad certificadora de la institución, o uno autofirmado para la red interna:

```bash
# EJECUTAR EN EL SERVIDOR (certificado autofirmado, 1 año)
openssl req -x509 -nodes -newkey rsa:3072 -days 365 \
  -keyout /etc/ssl/private/sgrsi.key -out /etc/ssl/certs/sgrsi.crt \
  -subj "/CN=sgrsi.institucion.local" -addext "subjectAltName=DNS:sgrsi.institucion.local"
chmod 640 /etc/ssl/private/sgrsi.key && chgrp ssl-cert /etc/ssl/private/sgrsi.key
```

Activación:

```bash
# EJECUTAR EN EL SERVIDOR
a2enmod ssl headers
a2dissite 000-default
a2ensite sgrsi
apache2ctl configtest && systemctl reload apache2
```

Cookie de sesión (recomendado, no está en el código): en `/etc/php/8.2/apache2/conf.d/99-sgrsi.ini`

```ini
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Lax
expose_php = Off
display_errors = Off
log_errors = On
```

Luego `systemctl reload apache2`. (`session.cookie_secure = 1` exige acceder siempre por HTTPS; el sistema solo en HTTP, sin certificado, quedaría sin sesión.)

Verificación desde otro equipo de la red:

```bash
curl -kI https://sgrsi.institucion.local/paginas/login.html   # 200
curl -kI https://sgrsi.institucion.local/.env                  # 404
curl -kI https://sgrsi.institucion.local/app/                  # 404
```

## 4. Firewall (RNF5)

Política: denegar todo lo entrante y permitir solo **SSH (22)**, **HTTP (80)** y **HTTPS (443)** desde la red institucional. MySQL (3306) **no se abre**: solo es accesible desde el propio servidor por `127.0.0.1`/socket. Use **una** de las dos opciones (ufw o nftables), no ambas.

### Opción A: ufw

```bash
# EJECUTAR EN EL SERVIDOR. Permita SSH ANTES de activar, para no quedar fuera.
ufw default deny incoming
ufw default allow outgoing
ufw allow from 192.168.1.0/24 to any port 22 proto tcp comment 'SSH red institucional'
ufw allow from 192.168.1.0/24 to any port 80,443 proto tcp comment 'HTTP/HTTPS red institucional'
# No se crea ninguna regla para 3306
ufw enable
ufw status verbose
```

(Sustituya `192.168.1.0/24` por `RED_INST`. Para permitir HTTP/HTTPS desde cualquier origen use `ufw allow 80,443/tcp`.) `ufw enable` deja el servicio habilitado en el arranque.

### Opción B: nftables

```bash
# EJECUTAR EN EL SERVIDOR
apt install -y nftables
cat > /etc/nftables.conf <<'EOF'
#!/usr/sbin/nft -f
flush ruleset

table inet filtro {
    chain input {
        type filter hook input priority 0; policy drop;
        ct state established,related accept
        ct state invalid drop
        iif lo accept                                   # incluye MySQL local
        ip protocol icmp accept
        ip6 nexthdr icmpv6 accept
        tcp dport 22 ip saddr 192.168.1.0/24 accept     # SSH
        tcp dport { 80, 443 } ip saddr 192.168.1.0/24 accept
    }
    chain forward { type filter hook forward priority 0; policy drop; }
    chain output  { type filter hook output priority 0; policy accept; }
}
EOF
nft -c -f /etc/nftables.conf      # valida la sintaxis sin aplicar
systemctl enable --now nftables
nft list ruleset
```

Comprobación desde otro equipo: `nmap -p 22,80,443,3306 <ip-servidor>` debe mostrar 22/80/443 abiertos y 3306 cerrado o filtrado.

## 5. SSH endurecido (RNF7)

La administración remota es solo por SSH, con claves y sin acceso directo de `root`.

```bash
# EJECUTAR EN EL SERVIDOR
adduser admsgrsi && usermod -aG sudo admsgrsi      # usuario de administración
```

Desde el equipo del administrador, genere y copie la clave **antes** de desactivar las contraseñas:

```bash
# EJECUTAR EN EL EQUIPO DEL ADMINISTRADOR
ssh-keygen -t ed25519 -C "admsgrsi@sgrsi"
ssh-copy-id admsgrsi@sgrsi.institucion.local
ssh admsgrsi@sgrsi.institucion.local              # confirme que entra sin contraseña
```

En el servidor, `/etc/ssh/sshd_config.d/10-sgrsi.conf`:

```
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
KbdInteractiveAuthentication no
PermitEmptyPasswords no
MaxAuthTries 3
LoginGraceTime 30
AllowUsers admsgrsi
X11Forwarding no
```

```bash
# EJECUTAR EN EL SERVIDOR
sshd -t && systemctl reload ssh
```

Mantenga abierta la sesión actual y pruebe un ingreso nuevo desde otra terminal antes de cerrarla. Opcional: `apt install fail2ban` para bloquear intentos repetidos.

## 6. Respaldos periódicos de `PeartoS.A` (RNF6)

`mysqldump` + cron, con rotación. El script corre como `root`, que en Debian/MySQL se autentica por socket sin contraseña (no se guardan credenciales en el script). El esquema actual no define triggers ni procedimientos (el rol se deduce de las tablas de rol), pero `--routines --triggers --events` se mantiene por si más adelante se agregan.

```bash
# EJECUTAR EN EL SERVIDOR
install -d -m 700 /var/backups/sgrsi
cat > /usr/local/bin/respaldo_sgrsi.sh <<'EOF'
#!/bin/bash
# Respaldo de la base PeartoS.A con rotación
set -euo pipefail

DESTINO=/var/backups/sgrsi
RETENCION_DIAS=14
ARCHIVO="$DESTINO/PeartoS.A_$(date +%Y%m%d_%H%M%S).sql.gz"

umask 077
mysqldump --single-transaction --routines --triggers --events \
          --databases 'PeartoS.A' | gzip -9 > "$ARCHIVO"

gzip -t "$ARCHIVO"                      # verifica que el archivo no esté corrupto
find "$DESTINO" -name 'PeartoS.A_*.sql.gz' -type f -mtime +"$RETENCION_DIAS" -delete
logger -t respaldo_sgrsi "Respaldo OK: $ARCHIVO"
EOF
chmod 750 /usr/local/bin/respaldo_sgrsi.sh
/usr/local/bin/respaldo_sgrsi.sh && ls -lh /var/backups/sgrsi      # prueba manual
```

Programación diaria a las 02:30 (fuera del horario operativo), en `/etc/cron.d/sgrsi-respaldo`:

```
30 2 * * * root /usr/local/bin/respaldo_sgrsi.sh >> /var/log/respaldo_sgrsi.log 2>&1
```

Recomendado: copiar los respaldos a otro equipo (un respaldo en el mismo disco no protege de una falla del disco), por ejemplo con `rsync -a /var/backups/sgrsi/ usuario@equipo-respaldos:/ruta/` agregado al script o a otra entrada de cron, usando clave SSH dedicada.

### Restaurar

```bash
# EJECUTAR EN EL SERVIDOR
systemctl stop apache2                                  # evita escrituras durante la restauración
gunzip -c /var/backups/sgrsi/PeartoS.A_AAAAMMDD_HHMMSS.sql.gz | mysql
systemctl start apache2
```

El volcado trae `CREATE DATABASE` y `USE`, y reemplaza las tablas existentes (`DROP TABLE IF EXISTS` es el comportamiento por defecto de `mysqldump`). El usuario `sgrsi` y sus permisos no forman parte del volcado de la base: se mantienen en la base `mysql`. Pruebe la restauración periódicamente en un servidor de prueba.

## 7. Servicios con arranque automático y estabilidad (RNF17, RNF18, RNF19)

```bash
# EJECUTAR EN EL SERVIDOR
systemctl enable --now apache2 mysql
systemctl enable ufw          # si usa ufw; con nftables: systemctl enable nftables
systemctl enable ssh cron
```

Si prefiere **php-fpm** en lugar de `libapache2-mod-php` (ajuste `8.2` a su versión de PHP):

```bash
apt install -y php-fpm
a2dismod php8.2 mpm_prefork && a2enmod mpm_event proxy_fcgi setenvif
a2enconf php8.2-fpm
systemctl enable --now php8.2-fpm
systemctl restart apache2
```

Reinicio automático ante caídas (Apache no lo trae por defecto), con `systemctl edit apache2`:

```ini
[Service]
Restart=on-failure
RestartSec=5s
```

Comprobaciones, incluido un reinicio completo:

```bash
systemctl is-enabled apache2 mysql ssh cron       # todos "enabled"
systemctl --failed
reboot
# Tras reiniciar:
systemctl is-active apache2 mysql && ss -tlnp | grep -E ':(22|80|443|3306)\s'
```

Disponibilidad (RNF17): programe las actualizaciones que requieran reinicio y las tareas de mantenimiento fuera del horario operativo; el respaldo ya corre a las 02:30. Revise con regularidad `/var/log/apache2/sgrsi_error.log`, `journalctl -u mysql` y el espacio en disco (`df -h`), en especial `/var/backups/sgrsi`.

## 8. Lista de verificación final

| Requisito | Comprobación |
|---|---|
| RNF5 firewall | `ufw status verbose` (o `nft list ruleset`) solo permite 22, 80 y 443; `ss -tlnp` muestra 3306 solo en 127.0.0.1 |
| RNF6 respaldos | Existe `/etc/cron.d/sgrsi-respaldo` y hay archivos recientes en `/var/backups/sgrsi` |
| RNF7 SSH | `sshd -T \| grep -E 'permitrootlogin\|passwordauthentication'` devuelve `no` y `no` |
| RNF17, RNF18 | Reinicio automático de Apache configurado; actualizaciones de seguridad automáticas activas |
| RNF19 arranque | `systemctl is-enabled apache2 mysql` = `enabled`; el sistema responde tras `reboot` |
| RNF25 Debian | `cat /etc/os-release` muestra Debian GNU/Linux; el login carga y se puede iniciar sesión |
| RNF26 HTTP/HTTPS | `https://sgrsi.institucion.local/paginas/login.html` responde desde un equipo de la red; `/.env` da 404 |
