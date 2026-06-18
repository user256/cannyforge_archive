<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $src = ClassSet::fromDir(__DIR__ . '/src');

    $rules = [];

    // Settings carry configuration; they must not reach into the admin UI layer.
    $rules[] = Rule::allClasses()
        ->that(new HaveNameMatching('*Settings'))
        ->should(new NotDependsOnTheseNamespaces('CannyForge\\Archive\\Admin'))
        ->because('Settings must not also own admin UI.');

    $config->add($src, ...$rules);
};
