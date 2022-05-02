<?php

namespace App\Controller;

use Stevenmaguire\OAuth2\Client\Provider\Dropbox;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DropboxLoginController extends AbstractController
{
    public function index(Request $request, SessionInterface $session): Response
    {
        $session->set('dropbox-token', $this->getDropboxToken($request, $session));

        return $this->redirectToRoute('save_thumbnail', ['save' => 'dropbox',], 301);
    }

    protected function getDropboxToken(Request $request, SessionInterface $session): string
    {
        $provider = new Dropbox([
            'clientId' => $_ENV['DROPBOX_CLIENT_ID'],
            'clientSecret' => $_ENV['DROPBOX_CLIENT_SECRET'],
            'redirectUri' => $_ENV['APP_URL'] . '/dropbox_login',
        ]);

        if (!$request->get('code')) {
            $authUrl = $provider->getAuthorizationUrl();
            $session->set('oauth2state', $provider->getState());
            header('Location: ' . $authUrl);

            exit;
        } elseif (!$request->get('state') || ($request->get('state') !== $session->get('oauth2state'))) {
            $session->remove('oauth2state');

            exit('Invalid state');
        } else {
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
            ]);

            return $token->getToken();
        }
    }
}

