<?php

namespace App\Controller;

use App\Repository\CarRepository;
use App\Repository\CategoryRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(CarRepository $carRepository, CategoryRepository $categoryRepository, ReviewRepository $reviewRepository): Response
    {
        return $this->render('home/index.html.twig', [
            // Featured = the 3 most recently added cars that can be booked.
            'featured_cars' => $carRepository->findBy(['isAvailable' => true], ['id' => 'DESC'], 3),
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
            'ratings' => $reviewRepository->findAverageRatings(),
        ]);
    }
}
