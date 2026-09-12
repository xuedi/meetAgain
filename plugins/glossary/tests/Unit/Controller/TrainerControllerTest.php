<?php declare(strict_types=1);

namespace Plugin\Glossary\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Plugin\Glossary\Controller\TrainerController;
use Plugin\Glossary\Service\GlossaryService;
use Plugin\Glossary\Service\ProgressService;
use Plugin\Glossary\Service\TrainerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TrainerControllerTest extends TestCase
{
    public function testTheTrainerIsNotFoundWhileTheConfigTurnsItOff(): void
    {
        // Arrange
        $trainer = $this->createStub(TrainerService::class);
        $trainer->method('isEnabled')->willReturn(false);
        $controller = new TrainerController($this->createStub(GlossaryService::class), $trainer, $this->createStub(ProgressService::class));

        // Assert
        $this->expectException(NotFoundHttpException::class);

        // Act
        $controller->index(new Request());
    }
}
