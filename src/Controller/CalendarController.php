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
        $calendar = $this->generateCalendar(null, $entityManager);
        //dump($calendar); // This will output the calendar data to the Symfony profiler or the browser
        //die();
        return $this->render('calendar/index.html.twig', [
            'calendar' => $calendar,
        ]);
    }

    #[Route('/appointment/new', name: 'appointment_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $dateString = $request->query->get('date');
        $timeString = $request->query->get('time');
        $appointment = new Appointment();
        //dump($dateString);
        //dump($timeString);
        //die();
        if ($dateString && $timeString) {
            try {
                $date = new \DateTime($dateString);
                $time = new \DateTime($timeString);

                // Set the date and time separately
                $appointment->setDate($date);
                $appointment->setTime($time);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Invalid date or time format');
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

    private function generateCalendar(\DateTime $currentDate = null, EntityManagerInterface $entityManager): array
    {
        if (!$currentDate) {
            $currentDate = new \DateTime(); // Use the current date if none provided
        }

        $firstDayOfMonth = (clone $currentDate)->modify('first day of this month');
        $lastDayOfMonth = (clone $currentDate)->modify('last day of this month');

        // Fetch existing appointments within the current month
        $appointments = $entityManager->getRepository(Appointment::class)->createQueryBuilder('a')
            ->where('a.date BETWEEN :start AND :end')
            ->setParameter('start', $firstDayOfMonth)
            ->setParameter('end', $lastDayOfMonth)
            ->getQuery()
            ->getResult();

        // Prepare booked slots for quick lookup
        $bookedSlots = [];
        foreach ($appointments as $appointment) {
            $bookedSlots[$appointment->getDate()->format('Y-m-d')][$appointment->getTime()->format('H:i')] = true;
        }

        // Generate the calendar with available time slots
        $calendar = [];
        $currentDay = (clone $firstDayOfMonth)->modify('last Sunday');

        while ($currentDay <= $lastDayOfMonth) {
            $isCurrentMonth = $currentDay->format('Y-m') === $currentDate->format('Y-m');
            $isToday = $currentDay->format('Y-m-d') === (new \DateTime())->format('Y-m-d');

            $timeSlots = [];
            for ($hour = 9; $hour < 18; $hour++) {
                $time = (clone $currentDay)->setTime($hour, 0);
                $dateKey = $currentDay->format('Y-m-d');
                $timeKey = $time->format('H:i');

                $isBooked = isset($bookedSlots[$dateKey][$timeKey]);

                $timeSlots[] = [
                    'time' => $time,
                    'available' => !$isBooked,
                ];
            }

            $calendar[] = [
                'date' => clone $currentDay,
                'isCurrentMonth' => $isCurrentMonth,
                'isToday' => $isToday,
                'timeSlots' => $timeSlots,
            ];

            $currentDay->modify('+1 day');
        }

        return $calendar;
    }

}
