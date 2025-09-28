<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Repository\ReportRepository;
use App\Message\GenerateReportMessage;
use App\Entity\Reading;
use App\Entity\Report;

#[AsController]
class ReportController extends AbstractController
{
    private EntityManagerInterface $em;
    private Security $security;

    public function __construct(Security $security, EntityManagerInterface $em, HttpClientInterface $httpClient, LoggerInterface $logger, S3Client $s3Client) {
        $this->security = $security;
        $this->em = $em;
        $this->s3Client = $s3Client;
    }

    /*
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
                'type' => Report::TYPE_DAILY,
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
            $report->setType(Report::TYPE_DAILY);
            $report->setFilename($filename);

            $this->em->persist($report);
        }

        $this->em->flush();

        return new Response('Daily reports generated successfully.');
    }
    */

    /*
    #[Route('/generate-weekly-reports', name: 'generate_weekly_reports')]
    public function generateWeeklyReports(): Response
    {
        $endDate = (new \DateTime('today'))->setTime(0, 0);
        $startDate = (clone $endDate)->modify('-7 days');

        $conn = $this->em->getConnection();
        $userIds = $conn->fetchFirstColumn('
            SELECT DISTINCT user_id FROM reading
            WHERE created_at >= :start AND created_at < :end', [
            'start' => $startDate->format('Y-m-d H:i:s'),
            'end' => $endDate->format('Y-m-d H:i:s'),
        ]);

        foreach ($userIds as $userId) {
            $existingReport = $this->em->getRepository(Report::class)->findOneBy([
                'user' => $userId,
                'date' => $startDate,
                'type' => Report::TYPE_WEEKLY,
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
            $qb->setParameter('start', $startDate);
            $qb->setParameter('end', $endDate);
            $readings = $qb->getQuery()->getResult();

            if (count($readings) === 0) {
                continue;
            }

            $values = array_map(fn($r) => $r->getValue(), $readings);
            $average = array_sum($values) / count($values);

            $user = $this->em->getRepository(\App\Entity\User::class)->find($userId);

            $readingSummary = implode(', ', array_map(function ($r) {
                return sprintf(
                    '%s: %s (Fasting: %s)',
                    $r->getCreatedAt()->format('Y-m-d H:i'),
                    $r->getValue(),
                    $r->isFasting() ? 'Yes' : 'No'
                );
            }, $readings));

            // Call Gemini API for analysis
            $geminiAnalysis = 'Analysis unavailable.';
            try {
                $geminiResponse = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=' . $_ENV['GEMINI_API_KEY'], [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'contents' => [[
                            'parts' => [[
                                'text' => "Return your response in plain text without any special formatting marks. Analyze the following glucose readings, fasting and non-fasting, over the past week and provide any observations, trends, or warnings:\n\n" . $readingSummary
                            ]]
                        ]],
                    ]
                ]);

                $statusCode = $geminiResponse->getStatusCode();
                $responseContent = $geminiResponse->getContent(false);
                $this->logger->info('Gemini API response', ['status' => $statusCode, 'content' => $responseContent]);

                if ($statusCode !== 200) {
                    $errorContent = json_decode($responseContent, true);
                    $errorMsg = $errorContent['error']['message'] ?? 'Unknown error.';
                    print_r($errorMsg); die;
                    throw new \Exception('Gemini API request failed with status ' . $statusCode . ': ' . $errorMsg);
                }

                $geminiData = json_decode($responseContent, true);
                // Try to extract the analysis text from possible response structures
                if (isset($geminiData['candidates'][0]['content']['parts'][0]['text'])) {
                    $geminiAnalysis = $geminiData['candidates'][0]['content']['parts'][0]['text'];
                } elseif (isset($geminiData['candidates'][0]['content']['parts'][0])) {
                    $geminiAnalysis = $geminiData['candidates'][0]['content']['parts'][0];
                } else {
                    $geminiAnalysis = 'Analysis not returned.';
                }
            } catch (\Throwable $e) {
                $this->logger->error('Gemini API failed: ' . $e->getMessage(), ['exception' => $e]);
            }

            $html = $this->renderView('weekly-report.html.twig', [
                'name' => $user->getName(),
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => (clone $endDate)->modify('-1 day')->format('Y-m-d'),
                'readings' => $readings,
                'timezone' => $user->getTimezone(),
                'average' => round($average, 2),
                'analysis' => $geminiAnalysis,
            ]);

            $filename = sprintf(
                'report_user%d_%s_to_%s.pdf',
                $userId,
                $startDate->format('Ymd'),
                (clone $endDate)->modify('-1 day')->format('Ymd')
            );
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
            $report->setDate($startDate);
            $report->setType(Report::TYPE_WEEKLY);
            $report->setFilename($filename);

            $this->em->persist($report);
        }

        $this->em->flush();

        return new Response('Weekly reports generated successfully.');
    }
    */


    #[Route('/generate-daily-reports', name: 'generate_daily_reports')]
    public function generateDailyReports(MessageBusInterface $bus): Response
    {
        $date = (new \DateTime('yesterday'))->setTime(0, 0);
        $endDate = (clone $date)->modify('+1 day');

        $conn = $this->em->getConnection();
        $userIds = $conn->fetchFirstColumn('SELECT DISTINCT user_id FROM reading WHERE created_at >= :start AND created_at < :end', [
            'start' => $date->format('Y-m-d H:i:s'),
            'end' => $endDate->format('Y-m-d H:i:s'),
        ]);

        foreach ($userIds as $userId) {
            $bus->dispatch(new GenerateReportMessage($userId, clone $date, clone $endDate, GenerateReportMessage::TYPE_DAILY));
        }

        return new Response('Daily reports enqueued.');
    }

    #[Route('/generate-weekly-reports', name: 'generate_weekly_reports')]
    public function generateWeeklyReports(MessageBusInterface $bus): Response
    {
        $endDate = (new \DateTime('today'))->setTime(0, 0);
        $startDate = (clone $endDate)->modify('-7 days');

        $conn = $this->em->getConnection();
        $userIds = $conn->fetchFirstColumn('
            SELECT DISTINCT user_id FROM reading
            WHERE created_at >= :start AND created_at < :end', [
            'start' => $startDate->format('Y-m-d H:i:s'),
            'end' => $endDate->format('Y-m-d H:i:s'),
        ]);

        foreach ($userIds as $userId) {
            $bus->dispatch(new GenerateReportMessage($userId, clone $startDate, clone $endDate, GenerateReportMessage::TYPE_WEEKLY));
        }

        return new Response('Weekly reports enqueued.');
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

    /*
    #[Route('/download-report/{filename}', name: 'download_report')]
    public function downloadReport(string $filename): Response
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/data/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Report not found.');
        }

        return new BinaryFileResponse($filePath, 200, [], true, null, false, true);
    }
    */

    #[Route('/download-report/{filename}', name: 'download_report')]
    public function downloadReport(string $filename): Response
    {
        $bucket = $_ENV['AWS_BUCKET'];
        $s3Key = 'reports/' . $filename;

        try {
            $result = $this->s3Client->getObject([
                'Bucket' => $bucket,
                'Key' => $s3Key,
            ]);

            $bodyStream = $result['Body'];

            return new StreamedResponse(function () use ($bodyStream) {
                while (!$bodyStream->eof()) {
                    echo $bodyStream->read(1024);
                    flush();
                }
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (S3Exception $e) {
            throw $this->createNotFoundException('Report not found');
        }
    }

}
