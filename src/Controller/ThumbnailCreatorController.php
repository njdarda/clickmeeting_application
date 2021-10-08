<?php

namespace App\Controller;

use App\Entity\ImageUpload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;

class ThumbnailCreatorController extends AbstractController
{
    public function index(Request $request, SluggerInterface $slugger, SessionInterface $session): Response
    {
        $imageUpload = new ImageUpload();

        $form = $this->createFormBuilder($imageUpload)
            ->add('source', FileType::class, [
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '8024k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Please upload a PNG or JPG image.',
                    ]),
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Create thumbnail'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('source')->getData();

            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $uniqueFilename = $slugger->slug($originalFilename).'_'.uniqid();

            if ($imageFile->getMimeType() === 'image/png') {
                $image = imagecreatefrompng($imageFile->getPathname());
                $fileName = $uniqueFilename.'.png';
                imagepng(imagescale($image, 150), $this->getParameter('upload_directory').'/'.$fileName);

                $session->set('filename', $fileName);
                return $this->redirectToRoute('save_thumbnail', [], 301);
            } elseif ($imageFile->getMimeType() === 'image/jpeg') {
                $image = imagecreatefromjpeg($imageFile->getPathname());
                $fileName = $uniqueFilename.'.jpg';
                imagejpeg(imagescale($image, 150), $this->getParameter('upload_directory').'/'.$fileName);

                $session->set('filename', $fileName);
                return $this->redirectToRoute('save_thumbnail', [], 301);
            }
        }

        return $this->renderForm('views/thumbnail_creator.html.twig', [
            'form' => $form,
        ]);
    }

}

