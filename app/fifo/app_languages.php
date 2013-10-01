<?php

	// queues

	$text['title-queues']['en-us'] = "Queues";
	$text['title-queues']['fr-fr'] = "Queues";
	$text['title-queue_add']['en-us'] = "Queue Add";
	$text['title-queue_add']['fr-fr'] = "Ajouter une Queue Add";
	$text['title-queue_edit']['en-us'] = "Queue Edit";
	$text['title-queue_edit']['fr-fr'] = "Editer la Queue";
	$text['title-queue_detail_add']['en-us'] = "Queue Detail Add";
	$text['title-queue_detail_add']['fr-fr'] = "Ajouter Détails";
	$text['title-queue_detail_edit']['en-us'] = "Queue Detail Edit";
	$text['title-queue_detail_edit']['fr-fr'] = "Editer Détails";

	$text['header-queues']['en-us'] = "Queues";
	$text['header-queues']['fr-fr'] = "Queues";
	$text['header-queue_add']['en-us'] = "Queue Add";
	$text['header-queue_add']['fr-fr'] = "Ajouter une Queue";
	$text['header-queue_edit']['en-us'] = "Queue Edit";
	$text['header-queue_edit']['fr-fr'] = "Editer Queue";
	$text['header-agent_details']['en-us'] = "Agent Details";
	$text['header-agent_details']['fr-fr'] = "Détails de l'Agent";
	$text['header-conditions_and_actions']['en-us'] = "Conditions and Actions";
	$text['header-conditions_and_actions']['fr-fr'] = "Conditions et Actions";
	$text['header-queue_detail_add']['en-us'] = "Queue Detail Add";
	$text['header-queue_detail_add']['fr-fr'] = "Ajouter Détails";
	$text['header-queue_detail_edit']['en-us'] = "Queue Detail Edit";
	$text['header-queue_detail_edit']['fr-fr'] = "Editer Détails";
	$text['header-additional_information']['en-us'] = "Additional Information";
	$text['header-additional_information']['fr-fr'] = "Informations Additionnelles";

	$text['description-queues']['en-us'] = "Queues are used to setup waiting lines for callers. Also known as FIFO Queues.";
	$text['description-queues']['fr-fr'] = "Les queues sont également appelées files d'attentes ou FIFO queues.";
	$text['description-queue_add']['en-us'] = "In simple terms queues are holding patterns for callers to wait until someone is available to take the call. Also known as FIFO Queues.";
	$text['description-queue_add']['fr-fr'] = "En résumé, ces files d'attente diffusent un média aux appelants en attendant que quelqu'un soit disponible pour traiter cet appel.";
	$text['description-queue_edit']['en-us'] = "Queues are used to setup waiting lines for callers. Also known as FIFO Queues.";
	$text['description-queue_edit']['fr-fr'] = "Les queues sont également appelées files d'attentes ou FIFO queues.";
	$text['description-conditions_and_actions']['en-us'] = "The following conditions, actions and anti-actions are used in the dialplan to direct call flow. Each is processed in order until you reach the action dialplan_detail_tag which tells what action to perform. You are not limited to only one condition or action dialplan_detail_tag for a given extension.";
	$text['description-conditions_and_actions']['fr-fr'] = "Les conditions suivantes, actions et anti-actions sont utilisées par le plan de nuémrotation pour acheminer l'appel. Chaque appel est géré dans l'ordre jusqu'à atteindre l'action \"dialplan_detail_tag\" qui décide de l'action à effectuer. Il n'y a pas de limite sur le nombre de condition ou d'action pour chaque extension.";

	$text['label-name']['en-us'] = "Name";
	$text['label-name']['fr-fr'] = "Nom";
	$text['label-extension']['en-us'] = "Extension";
	$text['label-extension']['fr-fr'] = "Extension";
	$text['label-order']['en-us'] = "Order";
	$text['label-order']['fr-fr'] = "Ordre";
	$text['label-continue']['en-us'] = "Continue";
	$text['label-continue']['fr-fr'] = "Continue";
	$text['label-enabled']['en-us'] = "Enabled";
	$text['label-enabled']['fr-fr'] = "Actif";
	$text['label-description']['en-us'] = "Description";
	$text['label-description']['fr-fr'] = "Description";
	$text['label-agent_queue_extension']['en-us'] = "Queue Extension Number";
	$text['label-agent_queue_extension']['fr-fr'] = "Numéro de la file";
	$text['label-agent_loginout_extension']['en-us'] = "Login/Logout Extension Number";
	$text['label-agent_loginout_extension']['fr-fr'] = "Numéro de Login/Logout";
	$text['label-tag']['en-us'] = "Tag";
	$text['label-tag']['fr-fr'] = "Tag";
	$text['label-type']['en-us'] = "Type";
	$text['label-type']['fr-fr'] = "Type";
	$text['label-data']['en-us'] = "Data";
	$text['label-data']['fr-fr'] = "Données";
	$text['label-order']['en-us'] = "Order";
	$text['label-order']['fr-fr'] = "Ordre";
	$text['label-field']['en-us'] = "Field";
	$text['label-field']['fr-fr'] = "Champs";
	$text['label-expression']['en-us'] = "Expression";
	$text['label-expression']['fr-fr'] = "Expression";
	$text['label-application']['en-us'] = "Application";
	$text['label-application']['fr-fr'] = "Application";
	$text['label-data']['en-us'] = "Data";
	$text['label-data']['fr-fr'] = "Données";
	$text['label-value']['en-us'] = "Value";
	$text['label-value']['fr-fr'] = "Valeur";

	$text['description-name']['en-us'] = "The name the queue will be assigned.";
	$text['description-name']['fr-fr'] = "Le nom donné à la queue.";
	$text['description-extension']['en-us'] = "The number that will be assigned to the queue.";
	$text['description-extension']['fr-fr'] = "Numéro d'extension pour joindre la queue. ";
	$text['description-agent_queue_extension']['en-us'] = "The extension number for the Agent FIFO Queue. This is the holding pattern for agents waiting to service calls in the caller FIFO queue.";
	$text['description-agent_queue_extension']['fr-fr'] = "Numéro de la queue pour l'agent. Ce sera le numéro entré par l'agent pour souscrire aux appels de cette queue. ";
	$text['description-agent_loginout_extension']['en-us'] = "Agents use this extension number to login or logout of the Queue. After logging into the agent will be ready to receive calls from the Queue.";
	$text['description-agent_loginout_extension']['fr-fr'] = "Numéro utilisé par l'agent pour se connecter/déconnecter de la queue. Après s'être connecté, l'agent sera prêt à recevoir des les appels de la queue.";
	$text['description-continue']['en-us'] = "Continue in most cases should be set to false.";
	$text['description-continue']['fr-fr'] = "Continuer, Dans la plus part des cas à Non.";

	$text['option-true']['en-us'] = "True";
	$text['option-true']['fr-fr'] = "Oui";
	$text['option-false']['en-us'] = "False";
	$text['option-false']['fr-fr'] = "Non";
	$text['option-condition']['en-us'] = "Condition";
	$text['option-condition']['fr-fr'] = "Condition";
	$text['option-action']['en-us'] = "Action";
	$text['option-action']['fr-fr'] = "Action";
	$text['option-anti-action']['en-us'] = "Anti-Action";
	$text['option-anti-action']['fr-fr'] = "Anti-Action";
	$text['option-parameter']['en-us'] = "Parameter";
	$text['option-parameter']['fr-fr'] = "Paramètre";

	$text['button-add']['en-us'] = "Add";
	$text['button-add']['fr-fr'] = "Ajouter";
	$text['button-edit']['en-us'] = "Edit";
	$text['button-edit']['fr-fr'] = "Editer";
	$text['button-delete']['en-us'] = "Delete";
	$text['button-delete']['fr-fr'] = "Supprimer";
	$text['button-back']['en-us'] = "Back";
	$text['button-back']['fr-fr'] = "Retour";
	$text['button-save']['en-us'] = "Save";
	$text['button-save']['fr-fr'] = "Sauvegarder";

	$text['confirm-delete']['en-us'] = "Do you really want to delete this?";
	$text['confirm-delete']['fr-fr'] = "Voulez-vous vraiment faire cela?";

	$text['message-add']['en-us'] = "Add Completed";
	$text['message-add']['fr-fr'] = "Ajouté";
	$text['message-update']['en-us'] = "Update Completed";
	$text['message-update']['fr-fr'] = "Mis à jour";
	$text['message-delete']['en-us'] = "Delete Completed";
	$text['message-delete']['fr-fr'] = "Supprimé";
	$text['message-required']['en-us'] = "Please provide: ";
	$text['message-required']['fr-fr'] = "Merci d'indiquer: ";

?>
