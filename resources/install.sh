#!/usr/bin/env bash
set -euo pipefail

LOG_PREFIX="[acreexp]"
REPO_URL="${REPO_URL:-https://github.com/MrJuju0319/acre_exp.git}"
REPO_BRANCH="${REPO_BRANCH:-main}"

SRC_DIR="/usr/local/src/acre_exp"
VENV_DIR="/opt/spc-venv"
ETC_DIR="/etc/acre_exp"
STATE_DIR="/var/lib/acre_exp"
CFG_FILE="$ETC_DIR/config.yml"

BIN_STATUS="/usr/local/bin/acre_exp_status.py"
BIN_WATCHDOG="/usr/local/bin/acre_exp_watchdog.py"
SERVICE_FILE="/etc/systemd/system/acre-exp-watchdog.service"

export DEBIAN_FRONTEND=noninteractive

echo "$LOG_PREFIX Installation des paquets système"
sudo apt-get update -y
sudo apt-get install -y git python3 python3-venv python3-pip jq mosquitto-clients curl ca-certificates

echo "$LOG_PREFIX Préparation dossiers"
sudo mkdir -p /usr/local/src "$ETC_DIR" "$STATE_DIR"
sudo chmod 755 "$ETC_DIR" "$STATE_DIR"

echo "$LOG_PREFIX Clonage/mise à jour acre_exp"
if [[ ! -d "$SRC_DIR/.git" ]]; then
  sudo git clone --branch "$REPO_BRANCH" --depth 1 "$REPO_URL" "$SRC_DIR"
else
  sudo git -C "$SRC_DIR" fetch --depth 1 origin "$REPO_BRANCH"
  sudo git -C "$SRC_DIR" reset --hard "origin/$REPO_BRANCH"
fi

sudo find "$SRC_DIR" -type f \( -name "*.sh" -o -name "*.py" -o -name "*.service" \) -exec sed -i 's/\r$//' {} +

echo "$LOG_PREFIX Préparation venv Python"
if [[ ! -d "$VENV_DIR" ]]; then
  sudo python3 -m venv "$VENV_DIR"
fi

sudo "$VENV_DIR/bin/python" -m pip install --upgrade pip
sudo "$VENV_DIR/bin/pip" install --upgrade requests beautifulsoup4 pyyaml "paho-mqtt<2"

echo "$LOG_PREFIX Ecriture config $CFG_FILE"
sudo tee "$CFG_FILE" >/dev/null <<YAML
spc:
  host: "${SPC_HOST:-http://192.168.1.100}"
  user: "${SPC_USER:-Engineer}"
  pin: "${SPC_PIN:-1111}"
  language: ${SPC_LANG:-253}
  session_cache_dir: "$STATE_DIR"
  min_login_interval_sec: ${MIN_LOGIN_INTERVAL:-60}
mqtt:
  host: "${MQTT_HOST:-127.0.0.1}"
  port: ${MQTT_PORT:-1883}
  user: "${MQTT_USER:-}"
  pass: "${MQTT_PASS:-}"
  base_topic: "${MQTT_BASE_TOPIC:-acre_XXX}"
  client_id: "${MQTT_CLIENT_ID:-acre-exp}"
  qos: ${MQTT_QOS:-0}
  retain: ${MQTT_RETAIN:-true}
watchdog:
  refresh_interval: ${WD_REFRESH:-2}
  controller_refresh_interval: ${WD_CONTROLLER_REFRESH:-60}
  log_changes: ${WD_LOG_CHANGES:-true}
  information:
    zones: ${WD_INFO_ZONES:-true}
    secteurs: ${WD_INFO_SECTEURS:-true}
    doors: ${WD_INFO_DOORS:-true}
    outputs: ${WD_INFO_OUTPUTS:-true}
  controle:
    zones: ${WD_CTRL_ZONES:-true}
    secteurs: ${WD_CTRL_SECTEURS:-true}
    doors: ${WD_CTRL_DOORS:-true}
    outputs: ${WD_CTRL_OUTPUTS:-true}
YAML

sudo chmod 640 "$CFG_FILE"

echo "$LOG_PREFIX Installation scripts acre_exp"
sudo install -m 0755 "$SRC_DIR/acre_exp_status.py" "$BIN_STATUS"
sudo install -m 0755 "$SRC_DIR/acre_exp_watchdog.py" "$BIN_WATCHDOG"

sudo sed -i "1s|^#!.*python.*$|#!${VENV_DIR}/bin/python3|" "$BIN_STATUS"
sudo sed -i "1s|^#!.*python.*$|#!${VENV_DIR}/bin/python3|" "$BIN_WATCHDOG"

echo "$LOG_PREFIX Installation service systemd acre_exp"
sudo tee "$SERVICE_FILE" >/dev/null <<SYSTEMD
[Unit]
Description=ACRE SPC -> MQTT Watchdog
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
ExecStart=$BIN_WATCHDOG -c $CFG_FILE
Restart=always
RestartSec=3
User=root
Group=root
ReadWritePaths=$STATE_DIR $ETC_DIR

[Install]
WantedBy=multi-user.target
SYSTEMD

sudo systemctl daemon-reload
sudo systemctl enable acre-exp-watchdog.service
sudo systemctl restart acre-exp-watchdog.service

echo "$LOG_PREFIX Etat service acre_exp"
sudo systemctl --no-pager --full status acre-exp-watchdog.service || true

echo "$LOG_PREFIX Terminé"