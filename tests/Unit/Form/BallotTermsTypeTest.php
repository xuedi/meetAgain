<?php declare(strict_types=1);

namespace Tests\Unit\Form;

use App\Form\BallotTermsType;
use Module\Ballot\Contract\TallyMode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Contracts\Translation\TranslatorInterface;

final class BallotTermsTypeTest extends TestCase
{
    public function testBothTermsAreOfferedByDefault(): void
    {
        // Act
        $form = $this->factory()->create(BallotTermsType::class);

        // Assert
        static::assertTrue($form->has(BallotTermsType::FIELD_DURATION));
        static::assertTrue($form->has(BallotTermsType::FIELD_MODE));
    }

    public function testACallerAskingForTheDeadlineAloneGetsNoModeField(): void
    {
        // Act
        $form = $this->factory()->create(BallotTermsType::class, null, ['fields' => [BallotTermsType::FIELD_DURATION]]);

        // Assert
        static::assertTrue($form->has(BallotTermsType::FIELD_DURATION));
        static::assertFalse($form->has(BallotTermsType::FIELD_MODE));
    }

    public function testTheNoticeIsOnlyThereWhenACallerPassesOne(): void
    {
        // Act
        $without = $this->factory()->create(BallotTermsType::class)->createView();
        $with = $this->factory()->create(BallotTermsType::class, null, ['notice' => 'Only one venue exists.'])->createView();

        // Assert
        static::assertNull($without->vars['notice']);
        static::assertSame('Only one venue exists.', $with->vars['notice']);
    }

    public function testTheDefaultsApplyWhenTheOverlayWasNeverOpened(): void
    {
        // Act
        $terms = BallotTermsType::read([]);

        // Assert
        static::assertSame('+' . BallotTermsType::DEFAULT_DURATION_DAYS . ' days', $terms['deadline']);
        static::assertSame(TallyMode::Approval, $terms['tallyMode']);
    }

    public function testTheCallerOwnsWhichModeIsPreselected(): void
    {
        // Act
        $view = $this->factory()->create(BallotTermsType::class, null, ['mode' => TallyMode::Single])->createView();

        // Assert
        static::assertSame(TallyMode::Single->value, $view[BallotTermsType::FIELD_MODE]->vars['data']);
    }

    public function testAnUntouchedOverlayFallsBackToTheModeTheCallerAskedFor(): void
    {
        // Act
        $terms = BallotTermsType::read([], TallyMode::Single);

        // Assert
        static::assertSame(TallyMode::Single, $terms['tallyMode'], 'a consumer whose vote picks one value keeps that default');
    }

    public function testAnOutOfRangeDurationIsPulledBackIntoRange(): void
    {
        // Act
        $tooShort = BallotTermsType::read([BallotTermsType::FIELD_DURATION => '0']);
        $tooLong = BallotTermsType::read([BallotTermsType::FIELD_DURATION => '4000']);

        // Assert
        static::assertSame('+1 days', $tooShort['deadline']);
        static::assertSame('+90 days', $tooLong['deadline']);
    }

    private function factory(): FormFactoryInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return Forms::createFormFactoryBuilder()->addType(new BallotTermsType($translator))->getFormFactory();
    }
}
