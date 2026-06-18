<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $src = ClassSet::fromDir(__DIR__ . '/src');

    $rules = [];

    // The settings value objects are pure configuration data: they must never
    // reach into the admin UI (or the engine). Deptrac already forbids Contracts
    // from depending on anything internal; this states the intent by name.
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('CannyForge\\Archive\\Contracts\\Settings'))
        ->should(
            new NotDependsOnTheseNamespaces(
                'CannyForge\\Archive\\Admin',
                'CannyForge\\Archive\\Core'
            )
        )
        ->because('Settings value objects are pure data; they must not own admin UI or engine logic.');

    $config->add($src, ...$rules);
};
