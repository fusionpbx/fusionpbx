<?php

	//application details
		$apps[$x]['name'] = "Authentication";
		$apps[$x]['uuid'] = "a8a12918-69a4-4ece-a1ae-3932be0e41f1";
		$apps[$x]['category'] = "Core";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.1";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Provides an authentication framework with plugins to check if a user is authorized to login.";
		$apps[$x]['description']['en-gb'] = "Provides an authentication framework with plugins to check if a user is authorized to login.";
		$apps[$x]['description']['ar-eg'] = "يوفر إطارًا للمصادقة مع المكونات الإضافية للتحقق مما إذا كان المستخدم مصرحًا له بتسجيل الدخول.";
		$apps[$x]['description']['de-at'] = "Stellt ein Authentifizierungs-Framework mit Plugins bereit, um zu prüfen, ob ein Benutzer zur Anmeldung berechtigt ist.";
		$apps[$x]['description']['de-ch'] = "Stellt ein Authentifizierungs-Framework mit Plugins bereit, um zu prüfen, ob ein Benutzer zur Anmeldung berechtigt ist.";
		$apps[$x]['description']['de-at'] = "Stellt ein Authentifizierungs-Framework mit Plugins bereit, um zu prüfen, obsich ein Benutzer anmelden darf.";
		$apps[$x]['description']['el-gr'] = "Παρέχει ένα πλαίσιο ελέγχου ταυτότητας με προσθήκες για να ελέγχει εάν ένας χρήστης είναι εξουσιοδοτημένος να συνδεθεί.";		
		$apps[$x]['description']['es-cl'] = "Proporciona un marco de autenticación con complementos para verificar si un usuario está autorizado para iniciar sesión.";
		$apps[$x]['description']['es-mx'] = "Proporciona un marco de autenticación con complementos para verificar si un usuario está autorizado para iniciar sesión.";
		$apps[$x]['description']['fr-ca'] = "Fournit un cadre d'authentification avec des plugins pour vérifier si un utilisateur est autorisé à se connecter.";
		$apps[$x]['description']['fr-fr'] = "Fournit un cadre d'authentification avec des plugins pour vérifier si un utilisateur est autorisé à se connecter.";
		$apps[$x]['description']['he-il'] = "מספק מסגרת אימות עם תוספים כדי לבדוק אם משתמש מורשה להתחבר.";
		$apps[$x]['description']['it-it'] = "Fornisce un framework di autenticazione con plug-in per verificare se un utente è autorizzato ad accedere.";
		$apps[$x]['description']['nl-nl'] = "Biedt een authenticatiekader met plug-ins om te controleren of een gebruiker geautoriseerd is om in te loggen.";
		$apps[$x]['description']['pl-pl'] = "Zapewnia strukturę uwierzytelniania z wtyczkami do sprawdzania, czy użytkownik jest upoważniony do logowania.";
		$apps[$x]['description']['pt-br'] = "Fornece uma estrutura de autenticação com plug-ins para verificar se um usuário está autorizado a fazer login.";
		$apps[$x]['description']['pt-pt'] = "Fornece uma estrutura de autenticação com plug-ins para verificar se um usuário está autorizado a fazer login.";
		$apps[$x]['description']['ro-ro'] = "Oferă un cadru de autentificare cu pluginuri pentru a verifica dacă un utilizator este autorizat să se autentifice.";
		$apps[$x]['description']['ru-ru'] = "Предоставляет платформу проверки подлинности с плагинами для проверки авторизации пользователя.";
		$apps[$x]['description']['sv-se'] = "Tillhandahåller ett autentiseringsramverk med plugins för att kontrollera om en användare är behörig att logga in.";
		$apps[$x]['description']['uk-ua'] = "Надає структуру автентифікації з плагінами, щоб перевірити, чи користувач авторизований для входу.";
		$apps[$x]['description']['tr-tr'] = "Bir kullanıcının oturum açmaya yetkili olup olmadığını kontrol etmek için eklentilerle bir kimlik doğrulama çerçevesi sağlar.";

	//default settings
		$y=0;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "309c4b74-711a-4a73-9408-412e5d089b59";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "authentication";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "methods";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "array";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "database";
		$apps[$x]['default_settings'][$y]['default_setting_order'] = "10";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "bc31a3f4-671b-44ca-8724-64ec077eed0b";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "authentication";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "methods";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "array";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "email";
		$apps[$x]['default_settings'][$y]['default_setting_order'] = "20";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "ab6ecf21-28e8-4caf-a04e-8667ec702f37";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "authentication";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "methods";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "array";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "totp";
		$apps[$x]['default_settings'][$y]['default_setting_order'] = "30";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "";
                $y++;
                $apps[$x]['default_settings'][$y]['default_setting_uuid'] = "59295a4c-7315-4059-aa11-60b6e2f4db48";
                $apps[$x]['default_settings'][$y]['default_setting_category'] = "authentication";
                $apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "email_send_mode";
                $apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
                $apps[$x]['default_settings'][$y]['default_setting_value'] = "email_queue";
                $apps[$x]['default_settings'][$y]['default_setting_order'] = "40";
                $apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
                $apps[$x]['default_settings'][$y]['default_setting_description'] = "Options: email_queue, direct";

?>
