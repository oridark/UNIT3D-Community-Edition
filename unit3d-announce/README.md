# UNIT3D-Announce

High-performance backend BitTorrent tracker compatible with UNIT3D tracker software.

## Usage

```sh
# Go to where UNIT3D is already installed
$ cd /var/www/html

# Clone this repository
$ git clone -b main https://github.com/HDInnovations/UNIT3D-Announce unit3d-announce

# Go into the repository
$ cd unit3d-announce

# Copy .env.example to .env
$ cp .env.example .env

# Adjust configuration as necessary
$ sudo nano .env

# Build the tracker
$ cargo build --release

# Go into UNIT3D's base directory
$ cd /var/www/html

# Add the required environment variables to UNIT3D'S .env file:
# (`TRACKER_HOST`, `TRACKER_PORT`, and `TRACKER_KEY`)
# These values should match their respective values in UNIT3D-Announce's .env file:
# (`LISTENING_IP_ADDRESS`, `LISTENING_PORT`, and `APIKEY`)
$ sudo nano .env

# Enable the external tracker in UNIT3D's config
$ sudo nano config/announce.php
```

## Reverse proxy

If you serve both UNIT3D and UNIT3D-Announce on the same domain, add the following `location` block to your nginx configuration already used for UNIT3D.

```sh
# Edit nginx config
$ sudo nano /etc/nginx/sites-enabled/default
```

Paste the following `location` block into the first `server` block immediately after the last existing `location` block.

```nginx
     location /announce/ {
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $host;
        proxy_pass http://aaa.bbb.ccc.ddd:eeee$request_uri;
        real_ip_header X-Forwarded-For;
        real_ip_recursive on;
        set_real_ip_from fff.ggg.hhh.iii;
    }
```

- `aaa.bbb.ccc.ddd:eeee` is the local listening IP address and port of UNIT3D-Announce. Set this to the `LISTENING_IP_ADDRESS` and `LISTENING_PORT` configured in the .env file.
- `fff.ggg.hhh.iii` is the public listening IP address of the nginx proxy used for accessing the frontend website. You can add additional `set_real_ip_from jjj.kkk.lll.mmm/nn;` lines for each additional proxy used so long as the proxy appends the proper values to the `X-Forwarded-For` header. Replace this with your proxy IP address.

Uncomment and set `REVERSE_PROXY_CLIENT_IP_HEADER_NAME` in the .env file to `X-Real-IP`.

```sh
# Reload nginx once finished
$ service nginx reload
```

## Supervisor

Add a supervisor config to run UNIT3D-Announce in the background.

### Configuration

```sh
# Edit supervisor config
sudo nano /etc/supervisor/conf.d/unit3d.conf
```

Paste the following block at the end of the file:

```supervisor
[program:unit3d-announce]
process_name=%(program_name)s_%(process_num)02d
command=/var/www/html/unit3d-announce/target/release/unit3d-announce
directory=/var/www/html/unit3d-announce
autostart=true
autorestart=false
user=root
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/announce.log
```

### Starting/Restarting UNIT3D-Announce

Reload supervisor

```sh
$ sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl reload
```

### Exiting UNIT3D-Announce

To gracefully exit the tracker:

```sh
sudo supervisorctl stop unit3d-announce:unit3d-announce_00
```

## Global Freeleech or Double Upload Events

> [!IMPORTANT]
> When using the Rust-based UNIT3D-Announce tracker, the global freeleech and double upload events are handled by the external tracker itself. This means you must activate the events in the `config/other.php` file within UNIT3D as normal to show the timer and then also within the `.env` file of the UNIT3D-Announce tracker to update user stats correctly.

To enable/disable global freeleech or double upload events, you need to adjust the following environment variables in the `.env` file and then [restart the tracker](#startingrestarting-unit3d-announce).

```sh
# The upload_factor is multiplied by 0.01 before being multiplied with
# the announced uploaded parameter and saved in the "credited" upload
# column. An upload_factor of 200 means global double upload.
#
# Default: 100
UPLOAD_FACTOR=200

# The download factor is multiplied by 0.01 before being multiplied
# with the announced downloaded parameter and saved in the "credited"
# download column. A download_factor of 0 means global freeleech.
#
# Default: 100
DOWNLOAD_FACTOR=0
```

## Configuration

### Reload

To reload the configuration without restarting the tracker, send the following curl:

```sh
curl -X POST "http://<LISTENING_IP_ADDRESS>:<LISTENING_PORT>/announce/<APIKEY>/config/reload"
```

## Performance

UNIT3D's PHP announce can handle ~250 HTTP requests per second per core on modern hardware.

Using the same hardware, UNIT3D-Announce handles ~50k HTTP requests per second per core (using wrk). Adding it behind an nginx proxy with TLS will reduce it to ~10k HTTP requests per second per core.
