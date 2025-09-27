<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Reading;
use App\Repository\ReadingRepository;

class ReadingController extends AbstractController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/api/readings', methods: ['GET'])]
    public function getReadings(ReadingRepository $readingRepository): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        $readings = $readingRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        return $this->json($readings);
    }

    #[Route('/api/readings', methods: ['POST'])]
    public function addReading(Request $request, ReadingRepository $readingRepository): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        $data = json_decode($request->getContent(), true);

        if (!isset($data['value']) || !is_numeric($data['value'])) {
            return $this->json(['error' => 'Invalid or missing "value" parameter'], 400);
        }

        $reading = new Reading();
        $reading->setValue((float) $data['value']);

        $reading->setNote($data['note'] ?? null);

        $isFasting = false;
        if (isset($data['isFasting'])) {
            if (is_bool($data['isFasting'])) {
                $isFasting = $data['isFasting'];
            } elseif (is_string($data['isFasting'])) {
                $isFasting = strtolower($data['isFasting']) === 'true';
            }
        }
        $reading->setIsFasting($isFasting);

        $reading->setCreatedAt(new \DateTimeImmutable());

        $reading->setUser($user);

        $readingRepository->add($reading, true);

        return $this->json($reading, 201);
    }

    #[Route('/api/readings/{id}', methods: ['DELETE'])]
    public function deleteReading(int $id, ReadingRepository $readingRepository): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $reading = $readingRepository->findOneById(['id' => $id, 'user' => $user]);
        if (!$reading) {
            return $this->json(['error' => 'Reading not found'], 404);
        } else {
            $readingRepository->remove($reading);
            return $this->json(null, 204);
        }
    }

    #[Route('/api/readings/{id}', methods: ['GET'])]
    public function getReading(int $id, ReadingRepository $readingRepository): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $reading = $readingRepository->findOneById(['id' => $id, 'user' => $user]);
        if (!$reading) {
            return $this->json(['error' => 'Reading not found'], 404);
        } else {
            return $this->json($reading);
        }
    }

}
