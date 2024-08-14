<?php
namespace App\Controller;

use App\Entity\Appointment;
use App\Form\AppointmentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormError;
use App\Repository\AppointmentRepository;
use App\Entity\User;

class CalendarController extends AbstractController {   
    private $validator;

    public function __construct(ValidatorInterface $validator) {
        $this->validator = $validator;
    }

    #[Route('/calendar', name: 'calendar')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response {
        // Generate the calendar data (e.g., weeks and days)
        $calendar = $this->generateCalendar(null, $entityManager);

        return $this->render('calendar/index.html.twig', [
            'calendar' => $calendar,
        ]);
    }

    #[Route('/appointment/new', name: 'appointment_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response {
        $dateString = $request->query->get('date');
        $timeString = $request->query->get('time');
        $appointment = new Appointment();

        if ($dateString && $timeString) {
            try {
                $date = new \DateTime($dateString);
                $time = new \DateTime($timeString);
                $appointment->setDate($date);
                $appointment->setTime($time);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Invalid date or time format');
                return $this->redirectToRoute('calendar');
            }
        }

        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $user = $appointment->getUser();

            // Check if a user with the same email already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);

            if ($existingUser) {
                // Update existing user details
                $existingUser->setName($user->getName());
                // Add any other fields you want to update
                $appointment->setUser($existingUser); // Associate the appointment with the existing user
            }

            $errors = $this->validator->validate($user);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $form->get('user')->get($error->getPropertyPath())->addError(new FormError($error->getMessage()));
                }
            }

            if ($form->isValid()) {
                $entityManager->persist($appointment);
                $entityManager->flush();

                // If request is an AJAX request, return a response that can be handled
                if ($request->isXmlHttpRequest()) {
                    return new Response('success');
                }

                return $this->redirectToRoute('calendar');
            }

            // If the form has validation errors and it's an AJAX request, return the form with errors
            if (!$form->isValid() && $request->isXmlHttpRequest()) {
                return $this->render('appointment/_form.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }

        // Default response for non-AJAX requests
        return $this->render('appointment/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function generateCalendar(\DateTime $currentDate = null, EntityManagerInterface $entityManager): array {
        if (!$currentDate) {
            $currentDate = new \DateTime(); // Use the current date if none provided
        }

        $firstDayOfMonth = (clone $currentDate)->modify('first day of this month');
        $lastDayOfMonth = (clone $currentDate)->modify('last day of this month');

        // Fetch existing appointments within the current month
        $appointments = $entityManager->getRepository(Appointment::class)->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->addSelect('u') // Select the user data
            ->where('a.date BETWEEN :start AND :end')
            ->setParameter('start', $firstDayOfMonth)
            ->setParameter('end', $lastDayOfMonth)
            ->getQuery()
            ->getResult();

        // Prepare booked slots for quick lookup
        $bookedSlots = [];
        foreach ($appointments as $appointment) {
            $user = $appointment->getUser();
            $bookedSlots[$appointment->getDate()->format('Y-m-d')][$appointment->getTime()->format('H:i')] = [
                'isBooked' => true,
                'user' => $user ? [
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                ] : null, // Ensure user data is included
                'appointmentId' => $appointment->getId(), // Include appointment ID
            ];
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
                    'user' => $isBooked ? $bookedSlots[$dateKey][$timeKey]['user'] : null,
                    'appointmentId' => $isBooked ? $bookedSlots[$dateKey][$timeKey]['appointmentId'] : null, // Ensure this is included
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

    #[Route('/appointment/delete/{id}', name: 'appointment_delete', methods: ['DELETE'])]
    public function delete(int $id, AppointmentRepository $appointmentRepository, EntityManagerInterface $entityManager, Request $request): Response {
        $appointment = $appointmentRepository->find($id);

        if (!$appointment) {
            if ($request->isXmlHttpRequest()) {
                return new Response('error', 404);
            }
            return $this->redirectToRoute('calendar');
        }

        $entityManager->remove($appointment);
        $entityManager->flush();

        if ($request->isXmlHttpRequest()) {
            return new Response('success');
        }

        return $this->redirectToRoute('calendar');
    }
}
