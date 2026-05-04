#!/usr/bin/env python3
import argparse
import json
import logging
import signal
import sys
import time
import urllib.parse
import urllib.request

import paho.mqtt.client as mqtt


RUNNING = True


def handle_stop(signum, frame):
    global RUNNING
    RUNNING = False


signal.signal(signal.SIGTERM, handle_stop)
signal.signal(signal.SIGINT, handle_stop)


def setup_logger(level_name: str):
    level = getattr(logging, level_name.upper(), logging.INFO)
    logging.basicConfig(
        level=level,
        format="[%(asctime)s] %(levelname)s : %(message)s",
        stream=sys.stdout,
    )


def call_jeedom(callback, apikey, topic, payload):
    data = urllib.parse.urlencode({
        "apikey": apikey,
        "topic": topic,
        "payload": payload,
    }).encode("utf-8")

    req = urllib.request.Request(callback, data=data, method="POST")

    with urllib.request.urlopen(req, timeout=10) as response:
        return response.read().decode("utf-8", errors="ignore")


def on_connect(client, userdata, flags, rc, properties=None):
    if rc == 0:
        logging.info("Connecté au broker MQTT")
        topic = userdata["base_topic"].rstrip("/") + "/#"
        client.subscribe(topic)
        logging.info("Abonnement MQTT : %s", topic)
    else:
        logging.error("Erreur connexion MQTT, rc=%s", rc)


def on_disconnect(client, userdata, rc, properties=None):
    logging.warning("Déconnexion MQTT, rc=%s", rc)


def on_message(client, userdata, msg):
    topic = msg.topic
    payload = msg.payload.decode("utf-8", errors="ignore")

    logging.debug("MQTT reçu topic=%s payload=%s", topic, payload)

    callback = userdata["callback"]
    apikey = userdata["apikey"]

    if not callback or not apikey:
        logging.warning("Callback ou API key manquant, message ignoré")
        return

    try:
        result = call_jeedom(callback, apikey, topic, payload)
        logging.debug("Réponse Jeedom : %s", result)
    except Exception as e:
        logging.error("Erreur callback Jeedom : %s", e)


def main():
    parser = argparse.ArgumentParser(description="Démon Jeedom acreexp MQTT")
    parser.add_argument("--mqtt-host", default="127.0.0.1")
    parser.add_argument("--mqtt-port", default=1883, type=int)
    parser.add_argument("--mqtt-user", default="")
    parser.add_argument("--mqtt-pass", default="")
    parser.add_argument("--base-topic", default="acre_XXX")
    parser.add_argument("--callback", required=True)
    parser.add_argument("--apikey", required=True)
    parser.add_argument("--loglevel", default="info", choices=["debug", "info", "warning", "error"])
    args = parser.parse_args()

    setup_logger(args.loglevel)

    logging.info("Démarrage acreexpd")
    logging.info("MQTT host=%s port=%s base_topic=%s", args.mqtt_host, args.mqtt_port, args.base_topic)
    logging.debug("Callback Jeedom=%s", args.callback)

    userdata = {
        "callback": args.callback,
        "apikey": args.apikey,
        "base_topic": args.base_topic,
    }

    client = mqtt.Client(
        mqtt.CallbackAPIVersion.VERSION2,
        client_id="jeedom-acreexpd",
        userdata=userdata,
    )

    if args.mqtt_user:
        client.username_pw_set(args.mqtt_user, args.mqtt_pass)

    client.on_connect = on_connect
    client.on_disconnect = on_disconnect
    client.on_message = on_message

    while RUNNING:
        try:
            logging.info("Connexion au broker MQTT...")
            client.connect(args.mqtt_host, args.mqtt_port, 60)
            client.loop_start()

            while RUNNING:
                time.sleep(1)

            client.loop_stop()
            client.disconnect()

        except Exception as e:
            logging.error("Erreur démon acreexpd : %s", e)
            time.sleep(5)

    logging.info("Arrêt acreexpd")


if __name__ == "__main__":
    main()