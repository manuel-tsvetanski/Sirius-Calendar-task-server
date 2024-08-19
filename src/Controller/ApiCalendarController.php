<?php
namespace App\Controller;

use App\Entity\Appointment;
use App\Form\AppointmentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Repository\AppointmentRepository;
use App\Entity\User;

#[Route('/api')] // Prefix all routes in this controller with /api
class ApiCalendarController extends AbstractController
{
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    #[Route('/calendar', name: 'api_calendar', methods: ['GET'])]
    public function getCalendarData(EntityManagerInterface $entityManager): JsonResponse
    {
        $calendar = $this->generateCalendar(null, $entityManager);

        return $this->json($calendar);
    }

    #[Route('/appointment/new', name: 'api_appointment_new', methods: ['POST'])]
    public function createAppointment(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $appointment = new Appointment();
        if (isset($data['date']) && isset($data['time'])) {
            try {
                $date = new \DateTime($data['date']);
                $time = new \DateTime($data['time']);
                $appointment->setDate($date);
                $appointment->setTime($time);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid date or time format'], Response::HTTP_BAD_REQUEST);
            }
        }

        // Ensure that only the fields that exist on the Appointment entity are submitted to the form
        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->submit($data, false);

        if ($form->isSubmitted() && !$form->isValid()) {
            // Collecting form errors
            $errors = [];
            foreach ($form->getErrors(true, false) as $error) {
                $errors[] = [
                    'field' => $error->getOrigin()->getName(),
                    'message' => $error->getMessage(),
                ];
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $user = $appointment->getUser();
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $validationErrors = [];
            foreach ($errors as $error) {
                $validationErrors[] = [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage(),
                ];
            }

            return new JsonResponse(['errors' => $validationErrors], Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        if ($existingUser) {
            $existingUser->setName($user->getName());
            $appointment->setUser($existingUser);
        }

        $entityManager->persist($appointment);
        $entityManager->flush();

        return new JsonResponse(['status' => 'success'], Response::HTTP_CREATED);
    }


    #[Route('/appointment/delete/{id}', name: 'api_appointment_delete', methods: ['DELETE'])]
    public function deleteAppointment(int $id, AppointmentRepository $appointmentRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $appointment = $appointmentRepository->find($id);

        if (!$appointment) {
            return new JsonResponse(['error' => 'Appointment not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($appointment);
        $entityManager->flush();

        return new JsonResponse(['status' => 'success'], Response::HTTP_OK);
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
}
