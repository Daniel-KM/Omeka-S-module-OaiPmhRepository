<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()->in(__DIR__);

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers([
        '-concat_wtihout_spaces',
        '-new_with_braces',
        '-pre_increment',
        'concat_with_spaces',
        'no_useless_else',
        'no_useless_return',
        'short_array_syntax',
        'short_echo_tag',
    ])
    ->finder($finder);
