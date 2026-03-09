# Plugin Jeedom - WAN Monitor

Ce plugin permet de surveiller le lien Internet principal (WAN1) et basculer vers un lien secondaire (WAN2) en cas de perte.

## Fonctionnalités

- Vérification de l'IP publique toutes les 5 minutes
- Comparaison avec une IP définie (WAN1)
- Exécution de commandes à la bascule WAN1 -> WAN2 ou retour
- Journalisation des événements
- Statistiques de durée d'utilisation de chaque lien

## Configuration

1. Installer le plugin depuis le Market ou manuellement
2. Ajouter un nouvel équipement de type `WAN Monitor`
3. Renseigner l'IP fixe de votre WAN1 dans la configuration
4. Créer les commandes Jeedom à exécuter en cas de bascule

## À venir

- Support notifications
- Choix du provider
- Détection jusqu'à 3 WAN
