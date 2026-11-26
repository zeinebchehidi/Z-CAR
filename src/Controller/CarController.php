<?php

namespace App\Controller;

use App\Entity\Car;
use App\Form\CarSearchType;
use App\Repository\CarRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CarController extends AbstractController
{
    #[Route('/cars', name: 'app_car_index', methods: ['GET'])]
    public function index(Request $request, CarRepository $carRepository, ReviewRepository $reviewRepository): Response
    {
        $searchForm = $this->createForm(CarSearchType::class);
        $searchForm->handleRequest($request);

        // Invalid filters (e.g. return date before pickup date) show the error and all cars.
        $filters = $searchForm->isSubmitted() && $searchForm->isValid() ? $searchForm->getData() : [];

        return $this->render('car/index.html.twig', [
            'searchForm' => $searchForm,
            'cars' => $carRepository->search($filters),
            'ratings' => $reviewRepository->findAverageRatings(),
        ]);
    }

    #[Route('/cars/{id}', name: 'app_car_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Car $car, ReviewRepository $reviewRepository): Response
    {
        $reviews = $reviewRepository->findBy(['car' => $car, 'isApproved' => true], ['createdAt' => 'DESC']);
        $average = $reviews
            ? array_sum(array_map(fn ($review) => $review->getRating(), $reviews)) / count($reviews)
            : null;

        return $this->render('car/show.html.twig', [
            'car' => $car,
            'reviews' => $reviews,
            'averageRating' => $average,
        ]);
    }
}
