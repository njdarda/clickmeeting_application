<?php

namespace App\Controller;

use App\Entity\ImageUpload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;

class ThumbnailCreatorController extends AbstractController
{
    public function index(): Response
    {
        $name = "NJ Darda";

        $task = new ImageUpload();
        $task->setSource('');
        $task->setTarget('Amazon S3');

        $form = $this->createFormBuilder($task)
            ->add('source', FileType::class)
            ->add('target', ChoiceType::class, [
                'choices' => [
                    'Disk' => 'disk',
                    'Amazon S3' => 's3',
                    'Dropbox' => 'dropbox',
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Create thumbnail'])
            ->getForm();

        return $this->renderForm('views/image_resize.html.twig', [
            'name' => $name,
            'form' => $form,
        ]);
    }
}

