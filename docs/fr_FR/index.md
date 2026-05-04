# ACRE SPC MQTT

Ce plugin intègre `acre_exp` dans Jeedom.

## Configuration

Renseignez :

- l'adresse de la centrale ACRE/SPC ;
- l'utilisateur web ;
- le PIN ;
- le broker MQTT ;
- le `base_topic`, par défaut `acre_XXX`.

## Démarrage

1. Sauvegardez la configuration.
2. Installez les dépendances.
3. Démarrez le démon.
4. Les équipements apparaîtront dès que les topics MQTT seront reçus.

## Dépannage

```bash
systemctl status acre-exp-watchdog.service
journalctl -u acre-exp-watchdog.service -f -n 100
mosquitto_sub -h 127.0.0.1 -t 'acre_XXX/#' -v
```
