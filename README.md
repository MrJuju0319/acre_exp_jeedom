# Jeedom plugin `acreexp` — ACRE SPC MQTT

Squelette de plugin Jeedom pour intégrer facilement [`acre_exp`](https://github.com/MrJuju0319/acre_exp).

## Principe

- `acre_exp` interroge la centrale ACRE/SPC et publie les états sur MQTT.
- Le plugin Jeedom installe/configure `acre_exp`.
- Le démon du plugin écoute les topics MQTT `base_topic/#`.
- À la réception des topics, il crée automatiquement les équipements Jeedom : zones, secteurs, portes, sorties et état centrale.
- Les commandes Jeedom publient sur les topics `.../set` via `mosquitto_pub`.

## Installation depuis Jeedom

1. Zipper le dossier `acreexp` en `acreexp.zip`.
2. Dans Jeedom : **Réglages → Système → Configuration → Mises à jour / Market** : activer l'installation depuis fichier ou GitHub si nécessaire.
3. Installer le plugin depuis le zip ou depuis un dépôt GitHub.
4. Activer le plugin.
5. Renseigner la configuration : centrale SPC, MQTT, base topic.
6. Lancer les dépendances.
7. Démarrer le démon.

## Points à adapter avant production

- Vérifier la compatibilité exacte avec ta version de Jeedom Core.
- Tester le callback `core/php/jeeAcreExp.php` sur ton réseau interne Jeedom.
- Ajouter une vraie documentation et un changelog publics si publication Market.
- Sécuriser les droits système si Jeedom ne tourne pas en root.

## Topics gérés

Le plugin s'appuie sur les topics déclarés par `acre_exp`, par exemple :

- `acre_XXX/zones/<id>/state`
- `acre_XXX/zones/<id>/entree`
- `acre_XXX/secteurs/<id>/state`
- `acre_XXX/doors/<id>/state`
- `acre_XXX/outputs/<id>/state`
- `acre_XXX/etat/<section>/<libellé>`

Les commandes publient sur :

- `acre_XXX/secteurs/<id>/set`
- `acre_XXX/doors/<id>/set`
- `acre_XXX/outputs/<id>/set`
- `acre_XXX/zones/<id>/set`
