<?php

namespace App\Controller;

use Stevenmaguire\OAuth2\Client\Provider\Dropbox;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DropboxLoginController extends AbstractController
{
    public function index(SessionInterface $session): Response
    {

        $session->set('dropbox-token', $this->getDropboxToken());

        return $this->redirectToRoute('save_thumbnail', ['save' => 'dropbox',], 301);
    }

    public function getDropboxToken(): string
    {
        $provider = new Dropbox([
            'clientId' => $_SERVER['DROPBOX_CLIENT_ID'],
            'clientSecret' => $_SERVER['DROPBOX_CLIENT_SECRET'],
             'redirectUri' => $_SERVER['APP_URL'] . 'dropbox_login',
        ]);

        if (!isset($_GET['code'])) {
            $authUrl = $provider->getAuthorizationUrl();
            $_SESSION['oauth2state'] = $provider->getState();
            header('Location: ' . $authUrl);
            exit;

        } elseif (empty($_GET['state']) || (isset($_SESSION) && $_GET['state'] !== $_SESSION['oauth2state'])) {

            unset($_SESSION['oauth2state']);
            exit('Invalid state');

        } else {
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $_GET['code'],
            ]);

            return $token->getToken();
        }
    }
}

