<?php

namespace App\Controller;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Spatie\Dropbox\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ThumbnailSaverController extends AbstractController
{
    public function index(Request $request, SessionInterface $session): Response
    {
        if (!$session->get('filename')) {
            return $this->redirectToRoute('index', [], 301);
        }

        if ($request->query->get('save') === 'dropbox') {
            $token = $session->get('dropbox-token');
            if (!$token) {
                return $this->redirect('dropbox_login');
            } else {
                $client = new Client($token);
                $client->upload(
                    '/clickmeeting_thumbnails/'.$session->get('filename'),
                    file_get_contents($this->getParameter('upload_directory').'/'.$session->get('filename'))
                );

                $session->remove('filename');

                return $this->render('views/thumbnail_saver.html.twig', [
                    'uploaded' => 'dropbox',
                ]);
            }
        } elseif ($request->query->get('save') === 'amazon') {
            $s3 = new S3Client([
                'version' => 'latest',
                'region' => 'eu-central-1',
                'credentials' => [
                    'key' => $_SERVER['AWS_ACCESS_KEY_ID'],
                    'secret' => $_SERVER['AWS_SECRET_ACCESS_KEY'],
                ],
            ]);

            try {
                $s3->putObject([
                    'Bucket' => $_SERVER['AWS_BUCKET_NAME'],
                    'Key' => $session->get('filename'),
                    'Body' => file_get_contents($this->getParameter('upload_directory').'/'.$session->get('filename')),
                ]);
            } catch (S3Exception $exception) {
                echo $exception->getMessage();
            }

            return $this->render('views/thumbnail_saver.html.twig', [
                'uploaded' => 'dropbox',
            ]);

        } elseif ($request->query->get('save') === 'file') {
            $response = new BinaryFileResponse($this->getParameter('upload_directory').'/'.$session->get('filename'));
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $session->get('filename')
            );

            $session->remove('filename');

            return $response;
        }

        return $this->render('views/thumbnail_saver.html.twig', [
        ]);
    }


}

