<?php
/**
 * This file is part of the maintenance package.
 *
 * @author (c) Friends Of REDAXO
 * @author <friendsof@redaxo.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
$addon = rex_addon::get('maintenance');
$secret = '';
if (rex::isFrontend() and $addon->getConfig('frontend_aktiv') != 'Deaktivieren' and $addon->getConfig('secret') != '')
{
    rex_login::startSession();
    if (rex_session('secret') != '')
    {
        $secret = rex_session('secret');
    }
	
	// GET-Parameter abfragen
    $checksecret = rex_request('secret', 'string', 0);
	
	//Überprüfen ob eingegebenes PW stimmt, wenn ja Session beschreiben, ansosnten unten PW-Fragment anzeigen
	if($addon->getConfig('type') == 'PW' && $checksecret === $this->getConfig('secret')) {
		// speichert den Code in der Session
		rex_set_session('secret', $checksecret);
        $secret = rex_session('secret');
	}
	// Wenn Type PW-Eingabe ist, und es noch keine Session gibt
	if($addon->getConfig('type') == 'PW' && !$secret) {
		$mpage = new rex_fragment();
		//$mpage->setVar('clang', rex_clang::getCurrent()->getCode()); // toDo: Frontendsprache mit übergeben, um Übersetzung im Fragment anzuzeigen (Bisher: "Universelles" Wording)
        $mpage = $mpage->parse('maintenance_page_pw_form.php');
		echo $mpage;
		unset($mpage);
        die();
		}
		

    // speichert den Code in der Session
    if ($checksecret)
    {
        $code = $this->getConfig('secret');
        if ($code === $checksecret)
        {
            rex_set_session('secret', $checksecret);
            $secret = rex_session('secret');
        }
    }
}
// Ausgabe abbrechen, wenn der übermittelte Code nicht stimmt.
if (rex::isFrontend() and $addon->getConfig('frontend_aktiv') != 'Deaktivieren' and $secret == '')
{
    $ips = "";
    $admin = "";
    $ips = explode(", ", $this->getConfig('ip'));
    if ($addon->getConfig('frontend_aktiv') == 'Aktivieren')
    {
        $session = rex_backend_login::hasSession();
        $redirect = 'inaktiv';
        if (rex_backend_login::createUser())
        {
            $admin = rex::getUser()->isAdmin();
        }
        if ($addon->getConfig('blockSession') == 'Inaktiv')
        {
            $redirect = 'inaktiv';
        }
        if ($addon->getConfig('blockSession') == 'Inaktiv' && in_array(rex_server('REMOTE_ADDR') , $ips))
        {
            $redirect = 'inaktiv';
        }
        if ($addon->getConfig('blockSession') == "Redakteure" && $admin == false && !in_array(rex_server('REMOTE_ADDR') , $ips))
        {
            $redirect = 'aktiv';
        }
        if ($addon->getConfig('blockSession') == "Redakteure" && $admin == true)
        {
            $redirect = 'inaktiv';
        }
        if (!$session)
        {
            $redirect = "aktiv";
        }
        if (in_array(rex_server('REMOTE_ADDR') , $ips))
        {
            $redirect = "inaktiv";
        }
        if ($redirect == 'aktiv')
        {
            $url = $this->getConfig('redirect_frontend');
            $mpage = new rex_fragment();
            $mpage = $mpage->parse('maintenance_page.php');
            rex_response::setStatus(rex_response::HTTP_MOVED_TEMPORARILY);
            if ($url != '')
            {
                rex_response::sendRedirect($url);
            }
            else
            {
                echo $mpage;
                die();
            }
        }
    }
    if ($addon->getConfig('frontend_aktiv') == 'Selfmade')
    {
        $session = rex_backend_login::hasSession();
        $selfmade = '';
        if ($this->getConfig('ip') != '' && in_array(rex_server('REMOTE_ADDR') , $ips))
        {
            $selfmade = "aktiv";
        }
        if (!$session)
        {
            $selfmade = "aktiv";
        }
        if (in_array(rex_server('REMOTE_ADDR') , $ips))
        {
            $selfmade = "inaktiv";
        }
        if ($session)
        {
            $selfmade = "inaktiv";
        }
        if ($selfmade == 'aktiv')
        {
            $check = $this->getConfig('frontend_aktiv');
            $this->setConfig('frontend_aktiv', $check);
        }
    }
}

if (rex::isBackend())
{
    $user = rex::getUser();
    if ($user)
    {
        if ($addon->getConfig('backend_aktiv') == '1')
        {
            $session = rex::getUser()->isAdmin();
            $redirect = '';
            if ($session == false)
            {
                $redirect = "aktiv";
            }
            if ($session == true)
            {
                $redirect = "inaktiv";
            }
            if ($redirect == 'aktiv')
            {
                $url = $this->getConfig('redirect_backend');
                $mpage = new rex_fragment();
                $mpage = $mpage->parse('maintenance_page_be.php');
                rex_response::setStatus(rex_response::HTTP_MOVED_TEMPORARILY);
                if ($url != '')
                {
                    rex_response::sendRedirect($url);
                }
                else
                {
                    echo $mpage;
                    die();
                }
            }
        }
    }
    if ($addon->getConfig('backend_aktiv') == '1')
    {
        rex_extension::register('OUTPUT_FILTER', function (rex_extension_point $magic)
        {
            $header = '<i class="maintenance rex-icon fa-exclamation-triangle">';
            $replace = '<i title="Mode: Lock Backend" class="rex-icon fa-exclamation-triangle aktivieren_backend">';
            $magic->setSubject(str_replace($header, $replace, $magic->getSubject()));
        });
    }
    if ($addon->getConfig('frontend_aktiv') == 'Aktivieren')
    {
        rex_extension::register('OUTPUT_FILTER', function (rex_extension_point $ep)
        {
            $suchmuster = '<i class="maintenance rex-icon fa-exclamation-triangle">';
            $ersetzen = '<i title="Mode: Lock Frontend" class="rex-icon fa-exclamation-triangle aktivieren_frontend">';
            $ep->setSubject(str_replace($suchmuster, $ersetzen, $ep->getSubject()));
        });
    }
    if ($addon->getConfig('frontend_aktiv') == 'Selfmade')
    {
        rex_extension::register('OUTPUT_FILTER', function (rex_extension_point $ep)
        {
            $suchmuster = '<i class="maintenance rex-icon fa-exclamation-triangle">';
            $ersetzen = '<i title="Mode: Own Solution" class="rex-icon fa-exclamation-triangle selfmade_frontend">';
            $ep->setSubject(str_replace($suchmuster, $ersetzen, $ep->getSubject()));
        });
    }
    rex_view::addJsFile($this->getAssetsUrl('dist/bootstrap-tokenfield.js'));
    rex_view::addJsFile($this->getAssetsUrl('dist/init_bootstrap-tokenfield.js'));
    rex_view::addCssFile($this->getAssetsUrl('dist/css/bootstrap-tokenfield.css'));
    rex_view::addCssFile($this->getAssetsUrl('css/maintenance.css'));
}

