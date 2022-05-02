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
                        'maxSize' => '8096k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Please upload a PNG or JPG image.',
                    ]),
                ],
            ])
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Create thumbnail', 'attr' => ['class' => 'btn btn-secondary save-button mb-2']]
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('source')->getData();

            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $uniqueFilename = $slugger->slug($originalFilename) . '_' . uniqid();

            /** @var \GdImage $image */
            $image = null;
            if ($imageFile->getMimeType() === 'image/png') {
                $image = imagecreatefrompng($imageFile->getPathname());
            } elseif ($imageFile->getMimeType() === 'image/jpeg') {
                $image = imagecreatefromjpeg($imageFile->getPathname());
            }

            if ($image) {
                $fileName = $uniqueFilename . '.png';

                $imageWidth = imagesx($image);
                $imageHeight = imagesy($image);
                $ratio = $imageWidth / $imageHeight;

                if ($imageWidth > 150 && $ratio > 1) {
                    $image = imagescale($image, 150, 150 / $ratio);
                } elseif ($imageHeight > 150 && $ratio < 1) {
                    $image = imagescale($image, $ratio * 150, 150);
                }
                imagepng($image, $this->getParameter('upload_directory') . '/' . $fileName);

                $session->set('filename', $fileName);

                return $this->redirectToRoute('save_thumbnail', [], 301);
            }
        }

        return $this->renderForm('views/thumbnail_creator.html.twig', [
            'form' => $form,
        ]);
    }

}

