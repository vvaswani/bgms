<?php

namespace App\Controller;

use App\Entity\Reading;
use App\Entity\Report;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\SecurityBundle\Security;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Repository\ReportRepository;

#[AsController]
class ReportController extends AbstractController
{
    private EntityManagerInterface $em;
    private Security $security;

    public function __construct(Security $security, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->em = $em;
    }

    #[Route('/generate-daily-reports', name: 'generate_daily_reports')]
    public function generateDailyReports(): Response
    {
        $date = (new \DateTime('yesterday'))->setTime(0, 0); // run for yesterday
        $endDate = (clone $date)->modify('+1 day');

        $conn = $this->em->getConnection();
        $userIds = $conn->fetchFirstColumn('SELECT DISTINCT user_id FROM reading WHERE created_at >= :start AND created_at < :end', [
            'start' => $date->format('Y-m-d H:i:s'),
            'end' => $endDate->format('Y-m-d H:i:s'),
        ]);

        foreach ($userIds as $userId) {
            $existingReport = $this->em->getRepository(Report::class)->findOneBy([
                'user' => $userId,
                'date' => $date,
            ]);

            if ($existingReport) {
                continue;
            }

            $qb = $this->em->getRepository(Reading::class)->createQueryBuilder('r')
                ->where('r.user = :user')
                ->andWhere('r.createdAt >= :start')
                ->andWhere('r.createdAt < :end')
                ->orderBy('r.createdAt', 'ASC');
            $qb->setParameter('user', $userId);
            $qb->setParameter('start', $date);
            $qb->setParameter('end', $endDate);
            $readings = $qb->getQuery()->getResult();

            if (count($readings) === 0) {
                continue;
            }

            $values = array_map(fn($r) => $r->getValue(), $readings);
            $average = array_sum($values) / count($values);

            $user = $this->em->getRepository(\App\Entity\User::class)->find($userId);

            $html = $this->renderView('daily-report.html.twig', [
                'name' => $user->getName(),
                'date' => $date->format('Y-m-d'),
                'readings' => $readings,
                'timezone' => $user->getTimezone(),
                'average' => round($average, 2),
            ]);

            $filename = sprintf('report_user%d_%s.pdf', $userId, $date->format('Ymd'));
            $path = $this->getParameter('kernel.project_dir') . '/data/' . $filename;

            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            file_put_contents($path, $dompdf->output());


            $report = new Report();
            $report->setUser($user);
            $report->setDate($date);
            $report->setFilename($filename);

            $this->em->persist($report);
        }

        $this->em->flush();

        return new Response('Daily reports generated successfully.');
    }

    #[Route('/api/reports', name: 'api_get_reports', methods: ['GET'])]
    public function getReports(ReportRepository $reportRepository): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        $reports = $reportRepository->findBy(['user' => $user],  ['date' => 'DESC']);
        return $this->json($reports);
    }

    #[Route('/download-report/{filename}', name: 'download_report')]
    public function downloadReport(string $filename): Response
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/data/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Report not found.');
        }

        return new BinaryFileResponse($filePath, 200, [], true, null, false, true);
    }
}
