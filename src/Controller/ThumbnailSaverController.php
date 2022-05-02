<?php

namespace App\Controller;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Spatie\Dropbox\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ThumbnailSaverController extends AbstractController
{
    private $info = '';
    private $errors = '';

    public function index(Request $request, SessionInterface $session): Response
    {
        if (!$session->get('filename')) {
            return $this->redirectToRoute('index', [], 301);
        }

        if ($request->query->get('save') === 'dropbox') {
            if ($this->saveToDropbox($session)) {
                $this->info = "Uploaded to Dropbox";
            } else {
                return $this->redirect('dropbox_login');
            }
        } elseif ($request->query->get('save') === 'amazon') {
            $this->saveToS3($session);
            $this->info = "Uploaded to Amazon S3";
        } elseif ($request->query->get('save') === 'disk') {
            $response = new BinaryFileResponse(
                $this->getParameter('upload_directory') . '/' . $session->get('filename')
            );
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $session->get('filename')
            );

            return $response;
        }

        $form = $this->createFormBuilder()
            ->add(
                'disk',
                ButtonType::class,
                ['label' => 'Save to file', 'attr' => ['class' => 'btn btn-secondary save-button mb-2']]
            )
            ->add(
                'dropbox',
                ButtonType::class,
                ['label' => 'Save to Dropbox', 'attr' => ['class' => 'btn btn-primary save-button mb-2']]
            )
            ->add(
                'amazon',
                ButtonType::class,
                ['label' => 'Save to Amazon S3', 'attr' => ['class' => 'btn btn-warning save-button mb-2']]
            )
            ->getForm();

        return $this->renderForm('views/thumbnail_saver.html.twig', [
            'imageSource' => '/upload/' . $session->get('filename'),
            'errors' => $this->errors,
            'info' => $this->info,
            'form' => $form,
        ]);
    }

    protected function saveToDropbox(SessionInterface $session): bool
    {
        $token = $session->get('dropbox-token');
        if (!$token) {
            return false;
        } else {
            $client = new Client($token);
            $client->upload(
                '/clickmeeting_thumbnails/' . $session->get('filename'),
                file_get_contents($this->getParameter('upload_directory') . '/' . $session->get('filename'))
            );
        }

        return true;
    }

    protected function saveToS3(SessionInterface $session): bool
    {
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => 'eu-central-1',
            'credentials' => [
                'key' => $_ENV['AWS_ACCESS_KEY_ID'],
                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
            ],
        ]);

        try {
            $s3->putObject([
                'Bucket' => $_ENV['AWS_BUCKET_NAME'],
                'Key' => $session->get('filename'),
                'Body' => file_get_contents($this->getParameter('upload_directory') . '/' . $session->get('filename')),
            ]);

            return true;
        } catch (S3Exception $exception) {
            $this->errors = $exception->getMessage();

            return false;
        }
    }
}

