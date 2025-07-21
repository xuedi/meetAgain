<?php declare(strict_types=1);

namespace Tests\Unit\Service\Activity\Messages;

use App\Entity\ActivityType;
use App\Service\Activity\Messages\EventImageUploaded;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use App\Service\ImageService;

class EventImageUploadedTest extends TestCase
{
    private MockObject|RouterInterface $router;
    private MockObject|ImageService $imageService;

    public function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->imageService = $this->createMock(ImageService::class);
    }

    public function testCanBuild(): void
    {
        $eventId = 42;
        $eventName = 'Test Event';
        $imageCount = 3;
        $eventUrl = '/event/42';
        $expectedText = 'uploaded 3 images to the event Test Event';
        $expectedHtml = 'uploaded <b>3</b> images to the event <a href="/event/42">Test Event</a>';

        $meta = ['event_id' => $eventId, 'images' => $imageCount];
        $eventNames = [$eventId => $eventName];

        $this->router->expects($this->once())
            ->method('generate')
            ->with('app_event_details', ['id' => $eventId])
            ->willReturn($eventUrl);

        $subject = new EventImageUploaded();
        $subject->injectServices($this->router, $this->imageService, $meta, [], $eventNames);

        // check returns
        $this->assertTrue($subject->validate());
        $this->assertEquals(ActivityType::EventImageUploaded, $subject->getType());
        $this->assertEquals($expectedText, $subject->render());
        $this->assertEquals($expectedHtml, $subject->render(true));
    }

    public function testCanCatchMissingEventId(): void
    {
        $this->expectExceptionObject(
            new InvalidArgumentException("Missing 'event_id' in meta in EventImageUploaded")
        );

        $subject = new EventImageUploaded();
        $subject->injectServices($this->router, $this->imageService, ['images' => 3]);
        $subject->validate();
    }

    public function testCanCatchNonNumericEventId(): void
    {
        $this->expectExceptionObject(
            new InvalidArgumentException("Value 'event_id' has to be numeric in 'EventImageUploaded'")
        );

        $subject = new EventImageUploaded();
        $subject->injectServices($this->router, $this->imageService, ['event_id' => 'not-a-number', 'images' => 3]);
        $subject->validate();
    }

    public function testCanCatchMissingImages(): void
    {
        $this->expectExceptionObject(
            new InvalidArgumentException("Missing 'images' in meta in EventImageUploaded")
        );

        $subject = new EventImageUploaded();
        $subject->injectServices($this->router, $this->imageService, ['event_id' => 42]);
        $subject->validate();
    }

    public function testCanCatchNonNumericImages(): void
    {
        $this->expectExceptionObject(
            new InvalidArgumentException("Value 'images' has to be numeric in 'EventImageUploaded'")
        );

        $subject = new EventImageUploaded();
        $subject->injectServices($this->router, $this->imageService, ['event_id' => 42, 'images' => 'not-a-number']);
        $subject->validate();
    }
}