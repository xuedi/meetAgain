<?php

namespace App\Twig;


use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetExistsExtension extends AbstractExtension
{
    public function __construct(private readonly KernelInterface $appKernel)
    {
    }

    public function getFunctions(): array
    {
        return array(
            new TwigFunction('asset_exists', [$this, 'assetExists']),
        );
    }

    public function assetExists($path): bool
    {
        $assets = $this->appKernel->getProjectDir() . '/assets/';
        $toCheck = realpath($assets . $path);

        if (!is_file($toCheck)) {
            return false;
        }

        return true;
    }

    public function getName(): string
    {
        return 'asset_exists';
    }
}
