<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Translation;
use App\Repository\TranslationRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class JustService
{
    public function __construct(private KernelInterface $appKernel)
    {
    }

    public function command(string $command): string
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('action');

        $process = new Process(['just', $command]);
        $process->setWorkingDirectory($this->appKernel->getProjectDir());
        $process->enableOutput();
        $process->start();
        $process->wait();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return (string)$stopwatch->stop('action');
    }
}
