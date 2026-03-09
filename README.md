# Plugin Jeedom - WAN Monitor

Plugin de surveillance de connexion Internet pour Jeedom. Il détecte automatiquement les changements de WAN (failover) et les coupures totales en comparant l'IP publique à intervalle régulier.

## Fonctionnalités

- **Surveillance automatique** de l'IP publique (intervalle configurable de 1 à 60 minutes)
- **Détection de 3 états** : WAN1 (connexion principale), WAN2 (connexion secondaire), DOWN (coupure totale)
- **Actions automatiques** : exécution de commandes Jeedom lors de chaque changement d'état
- **Historique complet** des basculements avec horodatage
- **Barre timeline 24h** sur la tuile du dashboard montrant visuellement les périodes WAN1/WAN2/DOWN
- **Statistiques de durée** par état : dernières 24h, 30 derniers jours, année en cours
- **Commandes utilisables dans les scénarios** Jeedom

## Installation

1. Installer le plugin depuis le Market Jeedom ou manuellement (copier dans `/var/www/html/plugins/wanmonitor/`)
2. Activer le plugin dans la gestion des plugins
3. L'IP publique actuelle est automatiquement détectée lors de l'installation

## Configuration du plugin

Dans la page de configuration du plugin (`Plugins > Monitoring > WAN Monitor > Configuration`) :

| Paramètre | Description | Exemple |
|-----------|-------------|---------|
| **WAN1 - Connexion principale** | Nom de votre FAI principal | `Free` |
| **IP fixe publique** | L'IP publique fixe de votre connexion principale | `83.42.12.65` |
| **WAN2 - Connexion secondaire** | Nom de votre FAI de secours | `Starlink` |
| **Fréquence de vérification** | Intervalle de vérification en minutes (1-60) | `5` |

> **Important** : votre connexion principale (WAN1) doit avoir une **IP publique fixe**. Le plugin compare l'IP publique actuelle à cette IP pour déterminer quel WAN est actif.

## Configuration de l'équipement

1. Aller dans `Plugins > Monitoring > WAN Monitor`
2. Cliquer sur `Ajouter` pour créer un nouvel équipement
3. Configurer le nom, l'objet parent et activer l'équipement
4. Sauvegarder — les commandes sont créées automatiquement

## Commandes disponibles

### Commandes info (lecture)

| Commande | Type | Description |
|----------|------|-------------|
| **Etat WAN** | string | État actuel : `WAN1`, `WAN2` ou `DOWN` |
| **FAI** | string | Nom du FAI actif (ou "Aucune connexion") |
| **IP Publique** | string | Adresse IP publique actuelle |
| **Historique** | string | Journal texte des derniers événements |
| **Stat WAN1 (min)** | numeric | Durée cumulée sur WAN1 en minutes |
| **Stat WAN2 (min)** | numeric | Durée cumulée sur WAN2 en minutes |

### Commandes action

| Commande | Description |
|----------|-------------|
| **Action vers WAN2** | Déclenchée automatiquement lors du passage vers WAN2 |
| **Action retour WAN1** | Déclenchée automatiquement lors du retour vers WAN1 |
| **Action coupure** | Déclenchée automatiquement lors d'une coupure totale (DOWN) |
| **Rafraîchir** | Force une vérification manuelle de l'état WAN |

## Tuile du dashboard

La tuile affiche :
- L'état actuel (WAN1/WAN2/DOWN) avec un code couleur (vert/orange/rouge)
- Le nom du FAI et l'IP publique
- Une **barre timeline 24h** montrant les périodes de chaque état
- Un bouton **calendrier** ouvrant une fenêtre avec l'historique complet et les statistiques de durée par période (24h / 30 jours / année)

## Utilisation dans les scénarios

La commande `Etat WAN` est historisée et peut être utilisée comme **déclencheur** ou dans des **conditions** de scénarios Jeedom.

### Exemple : Allumer le modem Starlink lors d'une coupure WAN1

**Contexte** : Vous avez une connexion principale (Free) et un modem Starlink branché sur une prise connectée. Quand la connexion Free tombe, vous voulez allumer automatiquement le Starlink. Quand Free revient, vous éteignez le Starlink.

#### Scénario "Failover WAN"

**Déclencheur** : `#[Maison][WAN Monitor][Etat WAN]#`

**Actions** :

```
SI #[Maison][WAN Monitor][Etat WAN]# == "WAN2" OU #[Maison][WAN Monitor][Etat WAN]# == "DOWN"
  ALORS
    // Allumer la prise du modem Starlink
    #[Salon][Prise Starlink][On]#

    // Envoyer une notification
    #[Notifications][Telegram][Envoi]#
    message="Coupure internet Free détectée, Starlink activé"

SI #[Maison][WAN Monitor][Etat WAN]# == "WAN1"
  ALORS
    // Éteindre la prise du modem Starlink (optionnel, avec un délai)
    // Attendre 10 minutes pour être sûr que Free est stable
    pause de 600 secondes
    #[Salon][Prise Starlink][Off]#

    #[Notifications][Telegram][Envoi]#
    message="Connexion Free rétablie, Starlink désactivé"
```

> **Astuce** : ajoutez un délai avant d'éteindre le Starlink lors du retour sur WAN1, pour éviter les basculements répétés si la connexion principale est instable.

## Logique de détection

Le plugin vérifie l'IP publique via `api.ipify.org` :

| IP publique | État | Signification |
|-------------|------|---------------|
| Correspond à l'IP WAN1 configurée | **WAN1** | Connexion principale active |
| IP différente de WAN1 | **WAN2** | Connexion secondaire active (failover) |
| Impossible de récupérer l'IP | **DOWN** | Aucune connexion internet |

## Changelog

### v0.2 (beta)
- Refonte de la tuile dashboard avec barre timeline 24h
- Ajout de la modale historique avec statistiques de durée
- Détection de l'état DOWN (coupure totale)
- Commande `action_down` pour les coupures
- Sélecteur de période pour les stats (24h / 30j / année)
- Correction : utilisation de PHP curl natif au lieu de shell_exec
- Correction : `updateWanStats` utilise le délai configuré

### v0.1
- Version initiale
- Surveillance de l'IP publique
- Détection WAN1/WAN2
- Actions automatiques lors des basculements
