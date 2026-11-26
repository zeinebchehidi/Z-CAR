<?php

namespace App\Controller\Admin;

use App\Entity\Car;
use App\Form\CarType;
use App\Repository\CarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin CRUD for cars, with photo upload (generated with make:crud, then adjusted).
 * Photos are saved in public/uploads/cars/; the Car entity only stores the filename.
 */
#[Route('/admin/cars')]
#[IsGranted('ROLE_ADMIN')]
final class CarController extends AbstractController
{
    #[Route('', name: 'admin_car_index', methods: ['GET'])]
    public function index(CarRepository $carRepository): Response
    {
        return $this->render('admin/car/index.html.twig', [
            'cars' => $carRepository->findBy([], ['brand' => 'ASC', 'model' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'admin_car_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $car = new Car();
        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $car);

            $entityManager->persist($car);
            $entityManager->flush();

            $this->addFlash('success', 'Car "'.$car->getBrand().' '.$car->getModel().'" created.');

            return $this->redirectToRoute('admin_car_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/car/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_car_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Car $car, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $car);

            $entityManager->flush();

            $this->addFlash('success', 'Car "'.$car->getBrand().' '.$car->getModel().'" updated.');

            return $this->redirectToRoute('admin_car_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/car/edit.html.twig', [
            'car' => $car,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_car_delete', methods: ['POST'])]
    public function delete(Request $request, Car $car, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$car->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token, please try again.');

            return $this->redirectToRoute('admin_car_index', [], Response::HTTP_SEE_OTHER);
        }

        // Keep the history: a car that was booked or reviewed is hidden instead of deleted.
        if (!$car->getReservations()->isEmpty() || !$car->getReviews()->isEmpty()) {
            $this->addFlash('error', 'Cannot delete "'.$car->getBrand().' '.$car->getModel().'": it has reservations or reviews. Uncheck "Available for booking" instead.');

            return $this->redirectToRoute('admin_car_index', [], Response::HTTP_SEE_OTHER);
        }

        $this->removeImage($car->getImage());
        $entityManager->remove($car);
        $entityManager->flush();

        $this->addFlash('success', 'Car "'.$car->getBrand().' '.$car->getModel().'" deleted.');

        return $this->redirectToRoute('admin_car_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * If a photo was uploaded: save it with a unique name, delete the old one, store the new filename.
     */
    private function handleImageUpload(FormInterface $form, Car $car): void
    {
        /** @var UploadedFile|null $file */
        $file = $form->get('imageFile')->getData();
        if (null === $file) {
            return; // no new photo: keep the current one
        }

        // Unique name so two uploads never overwrite each other, e.g. car-6750d1c2a8e3f.jpg
        $filename = uniqid('car-').'.'.$file->guessExtension();
        $file->move($this->getUploadDir(), $filename);

        $this->removeImage($car->getImage());
        $car->setImage($filename);
    }

    private function removeImage(?string $filename): void
    {
        if (null !== $filename) {
            (new Filesystem())->remove($this->getUploadDir().'/'.$filename);
        }
    }

    private function getUploadDir(): string
    {
        return $this->getParameter('kernel.project_dir').'/public/uploads/cars';
    }
}
