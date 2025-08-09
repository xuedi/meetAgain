<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Session\Consent;
use App\Entity\Session\ConsentType;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

readonly class GlobalService
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslationService $translationService,
        private DashboardService $dashboardService,
        private UserRepository $userRepo,
        private PluginService $pluginService,
        private MenuRepository $menuRepo,
    )
    {
    }

    public function getCurrentLocale(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            return $request->getLocale();
        }

        throw new RuntimeException('Cound not get current locale');
    }

    public function getLanguageCodes(): array
    {
        return $this->translationService->getLanguageCodes();
    }

    public function getPlugins(): iterable
    {
        return $this->pluginService->getActiveList();
    }

    public function getMenu(string $type): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            return $this->menuRepo->getAllSlugified($request->getLocale(), $type);
        }

        return $this->menuRepo->getAllSlugified('en');
    }

    public function getUserName(int $id): string
    {
        return $this->userRepo->findOneBy(['id' => $id])->getName();
    }

    public function hasNewMessages(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            return $request->getSession()->get('hasNewMessage', false);
        }

        return false;
    }

    public function getShowCookieConsent(): bool
    {
        $session = $this->requestStack->getCurrentRequest()?->getSession();
        if (!$session instanceof SessionInterface) {
            return true;
        }

        return Consent::getBySession($session)->getCookies() === ConsentType::Unknown;
    }

    public function getShowOsm(): bool
    {
        $session = $this->requestStack->getCurrentRequest()?->getSession();
        if (!$session instanceof SessionInterface) {
            return true;
        }

        return Consent::getBySession($session)->getOsm() === ConsentType::Granted;
    }

    public function getAdminAttention(): bool
    {
        return count($this->dashboardService->getNeedForApproval()) > 0;
    }

    public function getAlternativeLanguageCodes(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            $currentUri = $request->getRequestUri();
            $currentLocale = $request->getLocale();
            if (!str_starts_with($currentUri, '/_profiler')) {
                return $this->translationService->getAltLangList($currentLocale, $currentUri);
            }
        }

        return [];
    }
}
