<?php

namespace App\Controller;
// src/Controller/CalendarController.php

namespace App\Controller;

use App\Entity\Appointment;
use App\Form\AppointmentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalendarController extends AbstractController
{
    #[Route('/calendar', name: 'calendar')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Generate the calendar data (e.g., weeks and days)
        $calendar = $this->generateCalendar();

        return $this->render('calendar/index.html.twig', [
            'calendar' => $calendar,
        ]);
    }

    #[Route('/appointment/new', name: 'appointment_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Retrieve the date from the request
        $dateString = $request->query->get('date');
        $appointment = new Appointment();

        if ($dateString) {
            try {
                $date = new \DateTime($dateString);
                $appointment->setDate($date);
            } catch (\Exception $e) {
                // Handle the exception, e.g., log the error or show an error message
                $this->addFlash('error', 'Invalid date format');
                return $this->redirectToRoute('calendar');
            }
        }

        $form = $this->createForm(AppointmentType::class, $appointment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($appointment);
            $entityManager->flush();

            return $this->redirectToRoute('calendar');
        }

        return $this->render('appointment/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    private function generateCalendar(\DateTime $currentDate = null): array
    {
        if (!$currentDate) {
            $currentDate = new \DateTime(); // Use the current date if none provided
        }

        // Get the first and last days of the current month
        $firstDayOfMonth = (clone $currentDate)->modify('first day of this month');
        $lastDayOfMonth = (clone $currentDate)->modify('last day of this month');

        // Get the first day of the calendar (Sunday of the week containing the first day of the month)
        $startCalendar = (clone $firstDayOfMonth)->modify('last Sunday');

        // Get the last day of the calendar (Saturday of the week containing the last day of the month)
        $endCalendar = (clone $lastDayOfMonth)->modify('next Saturday');

        $calendar = [];
        $week = [];
        $currentDay = clone $startCalendar;

        // Loop through each day and build the calendar structure
        while ($currentDay <= $endCalendar) {
            $isCurrentMonth = $currentDay->format('Y-m') === $currentDate->format('Y-m');
            $isToday = $currentDay->format('Y-m-d') === (new \DateTime())->format('Y-m-d');

            $week[] = [
                'date' => clone $currentDay,
                'isCurrentMonth' => $isCurrentMonth,
                'isToday' => $isToday,
                'available' => $isCurrentMonth, // Example availability logic
            ];

            // If the week is complete (Saturday), start a new week
            if (count($week) === 7) {
                $calendar[] = $week;
                $week = [];
            }

            $currentDay->modify('+1 day');
        }

        return $calendar;
    }

}
