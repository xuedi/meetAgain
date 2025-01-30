<?php declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Activity;
use App\Entity\User;
use App\Entity\UserActivity;
use App\Service\ActivityService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActivityServiceTest extends TestCase
{
    private MockObject|EntityManagerInterface $emMock;
    private ActivityService $subject;

    protected function setUp(): void
    {
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->subject = new ActivityService($this->emMock);
    }

    public function testLoggingActivity(): void
    {
        $expectedMessage = 'User did login';
        $expectedUserMock = $this->createMock(User::class);
        $expectedUserActivity = UserActivity::Login;

        $this->emMock->expects($this->once())->method('flush');
        $this->emMock->expects($this->once())->method('persist')
            ->with($this->callback(function (Activity $actual) use ($expectedUserActivity, $expectedUserMock, $expectedMessage) {
                $this->assertSame($actual->getType(), $expectedUserActivity, 'Failed property match: Type');
                $this->assertSame($actual->getUser(), $expectedUserMock, 'Failed property match: User');
                $this->assertSame($actual->getMessage(), $expectedMessage, 'Failed property match: Message');

                return true;
            }));

        $this->subject->log($expectedUserActivity, $expectedUserMock, $expectedMessage);
    }
}
